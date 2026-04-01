<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesProduct;
use App\Models\Customer;
use App\Models\Income;
use App\Models\CompanyInformation;
use App\Models\ProductMovement;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Type;
use App\Models\BillSetting;
use App\Models\Discount;
use App\Models\Quotation;
use App\Models\Shift;
use App\Models\User;
use App\Models\ProductAvailableQuantity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    private function convertSalesToPurchaseUnits(Product $product, float $salesQuantity): float
    {
        $purchaseToTransferRate = (float) ($product->purchase_to_transfer_rate ?? 1);
        $transferToSalesRate = (float) ($product->transfer_to_sales_rate ?? 1);
        $purchaseToSalesRate = $purchaseToTransferRate * $transferToSalesRate;

        if ($purchaseToSalesRate <= 0) {
            return $salesQuantity;
        }

        return $salesQuantity / $purchaseToSalesRate;
    }

    private function deductGrnBatchesFifo(int $productId, float $salesQuantity): void
    {
        if ($salesQuantity <= 0) {
            return;
        }

        $product = Product::find($productId);
        if (!$product) {
            throw new \Exception("Product not found for FIFO deduction: {$productId}");
        }

        $purchaseQtyToDeduct = $this->convertSalesToPurchaseUnits($product, $salesQuantity);
        if ($purchaseQtyToDeduct <= 0) {
            return;
        }

        $remaining = $purchaseQtyToDeduct;

        $batches = ProductAvailableQuantity::where('product_id', $productId)
            ->where('available_quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        // Legacy records may not have per-batch rows yet.
        if ($batches->isEmpty()) {
            return;
        }

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $available = (float) $batch->available_quantity;
            if ($available <= 0) {
                continue;
            }

            $deduct = min($available, $remaining);
            $batch->decrement('available_quantity', $deduct);
            $remaining -= $deduct;

            $batch->refresh();
            if ((float) $batch->available_quantity <= 0 && (float) ($batch->quantity_in_transfer_unit ?? 0) <= 0 && (float) ($batch->quantity_in_sales_unit ?? 0) <= 0) {
                $batch->delete();
            }
        }

        if ($remaining > 0.000001) {
            throw new \Exception("Insufficient FIFO batch quantity for product: {$product->name}");
        }
    }

    private function restoreGrnBatchesLifo(int $productId, float $salesQuantity): void
    {
        if ($salesQuantity <= 0) {
            return;
        }

        $product = Product::find($productId);
        if (!$product) {
            return;
        }

        $purchaseQtyToRestore = $this->convertSalesToPurchaseUnits($product, $salesQuantity);
        if ($purchaseQtyToRestore <= 0) {
            return;
        }

        $latestBatch = ProductAvailableQuantity::where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->lockForUpdate()
            ->first();

        if ($latestBatch) {
            $latestBatch->increment('available_quantity', $purchaseQtyToRestore);
            return;
        }

        ProductAvailableQuantity::create([
            'product_id' => $productId,
            'batch_number' => null,
            'available_quantity' => $purchaseQtyToRestore,
            'quantity_in_transfer_unit' => 0,
            'quantity_in_sales_unit' => 0,
            'unit_id' => $product->purchase_unit_id ?? $product->measurement_unit_id,
            'goods_received_note_id' => null,
        ]);
    }

     public function index()
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }
        $userRole = (int) ($user->getAttribute('role') ?? -1);
        $userDivisionId = $user->getAttribute('division_id');

        // Generate next invoice number
        $lastSale = Sale::latest('id')->first();
        $billSetting = BillSetting::latest('id')->first();

        $nextInvoiceNo = $lastSale ? 'INV-' . str_pad($lastSale->id + 1, 6, '0', STR_PAD_LEFT) : 'INV-000001';

        $products = Product::select('id', 'name', 'barcode', 'retail_price', 'wholesale_price', 'shop_quantity_in_sales_unit', 'shop_low_stock_margin', 'image', 'brand_id', 'category_id', 'type_id', 'discount_id', 'sales_unit_id', 'division_id')
            ->with(['brand:id,name', 'category:id,name', 'type:id,name', 'discount:id,name,value,type','salesUnit:id,name'])
            ->when($userRole === 2 && $userDivisionId, function ($q) use ($userDivisionId) {
                $q->where('division_id', $userDivisionId);
            })
            ->orderByRaw('CASE WHEN shop_quantity_in_sales_unit <= shop_low_stock_margin THEN 1 ELSE 0 END')
            ->orderBy('name')
            ->get();

    $customers = Customer::select('id', 'name', 'status')
        ->orderBy('id', 'desc')
        ->get();

        $brands = Brand::select('id', 'name')
            ->orderBy('id', 'desc')
            ->get();

        $categories = Category::with('parent')
            ->select('id', 'name', 'parent_id')
            ->orderBy('id', 'desc')
            ->get();

        $types = Type::select('id', 'name')
            ->orderBy('id', 'desc')
            ->get();

        $discounts = Discount::select('id', 'name')
            ->orderBy('id', 'desc')
            ->get();

        $currencySymbol  = CompanyInformation::first();

        // Get quotations for conversion to sales (status = 1 only)
        $quotations = Quotation::select('id', 'quotation_no', 'quotation_date', 'total_amount', 'discount', 'customer_id', 'type')
            ->where('status', 1)
            ->with(['customer:id,name', 'products.product:id,name'])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($quotation) {
                return [
                    'id' => $quotation->id,
                    'quotation_no' => $quotation->quotation_no,
                    'quotation_date' => $quotation->quotation_date,
                    'total_amount' => $quotation->total_amount,
                    'discount' => $quotation->discount,
                    'customer_id' => $quotation->customer_id,
                    'customer_name' => $quotation->customer->name ?? 'Walk-in',
                    'customer_type' => $quotation->type == 2 ? 'wholesale' : 'retail',
                    'items' => $quotation->products->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'product_name' => $item->product->name ?? 'Unknown Product',
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                        ];
                    }),
                ];
            });

        $activeShift = Shift::open()
            ->where('user_id', Auth::id())
            ->latest('id')
            ->first();

        return Inertia::render('Sales/Index', [
            'invoice_no' => $nextInvoiceNo,
            'customers' => $customers,
            'products' => $products,
            'brands' => $brands,
            'categories' => $categories,
            'types' => $types,
            'billSetting' => $billSetting,
            'discounts' => $discounts,
            'currencySymbol' => $currencySymbol,
            'quotations' => $quotations,
            'activeShift' => $activeShift ? [
                'id' => $activeShift->id,
                'start_time' => optional($activeShift->start_time)?->format('Y-m-d H:i:s'),
            ] : null,
        ]);
    }

    public function store(Request $request)
    {

        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }
        $userId = (int) $user->getAuthIdentifier();
        $userDivisionId = $user->getAttribute('division_id');



        $request->validate([
            'invoice_no' => 'required|unique:sales,invoice_no',
            'customer_type' => 'required|in:retail,wholesale',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.payment_type' => 'required|in:0,1,2',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.card_type' => 'nullable|in:visa,mastercard',
            'paid_status' => 'required|in:0,1',
        ]);

        $payments = collect($request->payments ?? []);

        if ((int) $request->paid_status === 1 && $payments->isEmpty()) {
            return back()
                ->withErrors(['payments' => 'At least one payment method is required for paid sales.'])
                ->withInput();
        }

        foreach ($payments as $index => $payment) {
            if ((int) ($payment['payment_type'] ?? -1) === 1 && empty($payment['card_type'])) {
                return back()
                    ->withErrors(["payments.$index.card_type" => 'Card type is required for card payments.'])
                    ->withInput();
            }
        }

        $activeShift = Shift::open()
            ->where('user_id', Auth::id())
            ->latest('id')
            ->first();

        if (!$activeShift) {
            return back()->with('error', 'No active shift found. Start a shift before recording sales.');
        }




        try {
            DB::beginTransaction();

            // Calculate totals
            $totalAmount = collect($request->items)->sum(function ($item) {
                return $item['price'] * $item['quantity'];
            });

            $discount = $request->discount ?? 0;
            $netAmount = $totalAmount - $discount;

            // Calculate total paid from all payments
            $totalPaid = $payments->sum('amount');
            $balance = $netAmount - $totalPaid;

            // Convert customer_type to integer (1 = Retail, 2 = Wholesale)
            $type = $request->customer_type === 'wholesale' ? 2 : 1;

            // Determine second payment if two payment methods provided
            $primaryPayment = $request->payments[0] ?? null;
            $secondPayment = isset($request->payments[1]) ? $request->payments[1] : null;

            // Create sale
            $sale = Sale::create([
                'invoice_no' => $request->invoice_no,
                'type' => $type,
                'customer_id' => $request->customer_id ?: null,
                'user_id' => $userId,
                'division_id' => $userDivisionId,
                'shift_id' => $activeShift->id,
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'net_amount' => $netAmount,
                'paid_amount' => $totalPaid,
                'balance' => $balance,
                'paid_status' => (int) $request->paid_status,
                'payment1_type' => $primaryPayment ? (int) $primaryPayment['payment_type'] : null,
                'payment1_amount' => $primaryPayment ? (float) $primaryPayment['amount'] : null,
                'payment1_card_type' => ($primaryPayment && (int) ($primaryPayment['payment_type'] ?? -1) === 1)
                    ? ($primaryPayment['card_type'] ?? null) : null,
                'payment2_type' => $secondPayment ? (int) $secondPayment['payment_type'] : null,
                'payment2_amount' => $secondPayment ? (float) $secondPayment['amount'] : null,
                'payment2_card_type' => ($secondPayment && (int) ($secondPayment['payment_type'] ?? -1) === 1)
                    ? ($secondPayment['card_type'] ?? null) : null,
                'sale_date' => $request->sale_date,
            ]);

            // Create sale items and update stock
            // Proportionally distribute discount across all line items based on their subtotal
            foreach ($request->items as $item) {
                $lineTotal = $item['price'] * $item['quantity'];
                
                // Calculate proportional discount for this line item
                // Formula: (line_total / total_amount) * total_discount
                $lineDiscountAmount = $totalAmount > 0 
                    ? ($lineTotal / $totalAmount) * $discount 
                    : 0;
                
                // Net amount after discount for this line
                $lineNetAmount = $lineTotal - $lineDiscountAmount;
                
                // Calculate discounted unit price (actual price customer pays per unit)
                $discountedUnitPrice = $item['quantity'] > 0 
                    ? $lineNetAmount / $item['quantity']
                    : $item['price'];
                
                SalesProduct::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => round($discountedUnitPrice, 2), // Store discounted unit price
                    'total' => $lineTotal,
                    'discount_amount' => round($lineDiscountAmount, 2),
                    'net_amount' => round($lineNetAmount, 2),
                    'is_return' => false,
                ]);

                // Update product stock
                $product = Product::find($item['product_id']);
                $product->decrement('shop_quantity_in_sales_unit', $item['quantity']);

                // Keep GRN-wise availability in sync with sales using FIFO
                $this->deductGrnBatchesFifo((int) $item['product_id'], (float) $item['quantity']);

                // Record product movement (Sale - reduces stock)
                ProductMovement::recordMovement(
                    $item['product_id'],
                    ProductMovement::TYPE_SALE,
                    -$item['quantity'], // Negative for stock reduction
                    $request->invoice_no
                );

                // Log to Shop Inventory
                StoreInventoryController::logSale($item['product_id'], $item['quantity'], $sale->id);
            }

            // Create a single income record for the actual sale amount (not payment amount)
            // This ensures accurate income reporting when payments exceed sale amount
            $primaryPayment = $payments->get(0, ['payment_type' => 0, 'amount' => 0, 'card_type' => null]);

            Income::create([
                'sale_id' => $sale->id,
                'source' => 'Sale - ' . $sale->invoice_no,
                'amount' => $sale->net_amount, // Actual sale amount after discount
                'income_date' => $request->sale_date,
                'payment_type' => $primaryPayment['payment_type'] ?? 0, // Primary payment method
                'card_type' => ((int) ($primaryPayment['payment_type'] ?? -1) === 1)
                    ? ($primaryPayment['card_type'] ?? null)
                    : null,
                'transaction_type' => 'sale',
            ]);

            // If a quotation was used, update its status to 0
            if ($request->has('quotation_id') && $request->quotation_id) {
                \App\Models\Quotation::where('id', $request->quotation_id)->update(['status' => 0]);
            }

            DB::commit();

            return redirect()->route('sales.index')
                ->with('success', 'Sale completed successfully! Invoice: ' . $sale->invoice_no);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Sale failed: ' . $e->getMessage());
        }
    }

    /**
     * Return details for a single unpaid sale (GET /sales/{sale}/unpaid-details)
     */
    public function unpaidDetails(Request $request, Sale $sale)
    {
        $user = Auth::user();

        if ($user->role === 2 && $sale->division_id && $sale->division_id !== $user->division_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ((int) $sale->paid_status !== 0) {
            return response()->json(['error' => 'Only unpaid sales can be loaded.'], 422);
        }

        $sale->load(['products.product.salesUnit']);

        return response()->json([
            'id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'customer_id' => $sale->customer_id,
            'customer_type' => (int) $sale->type === 2 ? 'wholesale' : 'retail',
            'sale_date' => optional($sale->sale_date)->format('Y-m-d'),
            'discount' => (float) ($sale->discount ?? 0),
            'items' => $sale->products->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? 'Unknown Product',
                    'price' => (float) $item->price,
                    'quantity' => (int) $item->quantity,
                    'sale_unit' => $item->product?->salesUnit?->name ?? 'Not found',
                ];
            })->values(),
        ]);
    }

    /**
     * Update an existing unpaid sale and optionally mark it as paid (PATCH /sales/{sale}/complete-unpaid)
     */
    public function completeUnpaid(Request $request, Sale $sale)
    {
        $user = Auth::user();

        if ($user->role === 2 && $sale->division_id && $sale->division_id !== $user->division_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ((int) $sale->paid_status !== 0) {
            return back()->with('error', 'Only unpaid sales can be updated here.');
        }

        $request->validate([
            'invoice_no' => [
                'required',
                Rule::unique('sales', 'invoice_no')->ignore($sale->id),
            ],
            'customer_type' => 'required|in:retail,wholesale',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.payment_type' => 'required|in:0,1,2',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.card_type' => 'nullable|in:visa,mastercard',
            'paid_status' => 'required|in:0,1',
        ]);

        $payments = collect($request->payments ?? []);

        if ((int) $request->paid_status === 1 && $payments->isEmpty()) {
            return back()
                ->withErrors(['payments' => 'At least one payment method is required for paid sales.'])
                ->withInput();
        }

        foreach ($payments as $index => $payment) {
            if ((int) ($payment['payment_type'] ?? -1) === 1 && empty($payment['card_type'])) {
                return back()
                    ->withErrors(["payments.$index.card_type" => 'Card type is required for card payments.'])
                    ->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $existingItems = $sale->products()
                ->select('product_id', DB::raw('SUM(quantity) as qty'))
                ->groupBy('product_id')
                ->pluck('qty', 'product_id')
                ->map(fn($q) => (float) $q);

            $newItemsCollection = collect($request->items)
                ->groupBy('product_id')
                ->map(function ($rows) {
                    return $rows->sum(function ($row) {
                        return (float) ($row['quantity'] ?? 0);
                    });
                });

            $allProductIds = $existingItems->keys()->merge($newItemsCollection->keys())->unique();

            foreach ($allProductIds as $productId) {
                $oldQty = (float) ($existingItems[$productId] ?? 0);
                $newQty = (float) ($newItemsCollection[$productId] ?? 0);
                $diff = $newQty - $oldQty;

                if ($diff > 0) {
                    $product = Product::find($productId);
                    if (!$product || $product->shop_quantity_in_sales_unit < $diff) {
                        DB::rollBack();
                        return back()->with('error', 'Insufficient stock for one or more products.');
                    }

                    $product->decrement('shop_quantity_in_sales_unit', $diff);
                    $this->deductGrnBatchesFifo((int) $productId, (float) $diff);
                    ProductMovement::recordMovement(
                        $productId,
                        ProductMovement::TYPE_SALE,
                        -$diff,
                        $sale->invoice_no . ' (update)'
                    );
                } elseif ($diff < 0) {
                    $restoreQty = abs($diff);
                    $product = Product::find($productId);
                    if ($product) {
                        $product->increment('shop_quantity_in_sales_unit', $restoreQty);
                        $this->restoreGrnBatchesLifo((int) $productId, (float) $restoreQty);
                    }

                    ProductMovement::recordMovement(
                        $productId,
                        ProductMovement::TYPE_SALE,
                        $restoreQty,
                        $sale->invoice_no . ' (update)'
                    );
                }
            }

            $totalAmount = collect($request->items)->sum(function ($item) {
                return ((float) ($item['price'] ?? 0)) * ((float) ($item['quantity'] ?? 0));
            });

            $discount = (float) ($request->discount ?? 0);
            $netAmount = $totalAmount - $discount;
            $totalPaid = (float) $payments->sum('amount');
            $balance = $netAmount - $totalPaid;

            $type = $request->customer_type === 'wholesale' ? 2 : 1;
            $secondPayment = $payments->get(1);

            $sale->update([
                'invoice_no' => $request->invoice_no,
                'type' => $type,
                'customer_id' => $request->customer_id ?: null,
                'user_id' => Auth::id(),
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'net_amount' => $netAmount,
                'paid_amount' => $totalPaid,
                'balance' => $balance,
                'paid_status' => (int) $request->paid_status,
                'payment2_type' => $secondPayment ? (int) $secondPayment['payment_type'] : null,
                'payment2_amount' => $secondPayment ? (float) $secondPayment['amount'] : null,
                'payment2_card_type' => ($secondPayment && (int) ($secondPayment['payment_type'] ?? -1) === 1)
                    ? ($secondPayment['card_type'] ?? null) : null,
                'sale_date' => $request->sale_date,
            ]);

            $sale->products()->delete();

            foreach ($request->items as $item) {
                $lineTotal = ((float) $item['price']) * ((float) $item['quantity']);
                $lineDiscountAmount = $totalAmount > 0
                    ? ($lineTotal / $totalAmount) * $discount
                    : 0;
                $lineNetAmount = $lineTotal - $lineDiscountAmount;
                $discountedUnitPrice = (float) $item['quantity'] > 0
                    ? $lineNetAmount / (float) $item['quantity']
                    : (float) $item['price'];

                SalesProduct::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => round($discountedUnitPrice, 2),
                    'total' => $lineTotal,
                    'discount_amount' => round($lineDiscountAmount, 2),
                    'net_amount' => round($lineNetAmount, 2),
                    'is_return' => false,
                ]);
            }

            $primaryPayment = $payments->get(0, ['payment_type' => 0, 'amount' => 0, 'card_type' => null]);

            Income::updateOrCreate(
                [
                    'sale_id' => $sale->id,
                    'transaction_type' => 'sale',
                ],
                [
                    'source' => 'Sale - ' . $sale->invoice_no,
                    'amount' => $sale->net_amount,
                    'income_date' => $request->sale_date,
                    'payment_type' => $primaryPayment['payment_type'] ?? 0,
                    'card_type' => ((int) ($primaryPayment['payment_type'] ?? -1) === 1)
                        ? ($primaryPayment['card_type'] ?? null)
                        : null,
                ]
            );

            DB::commit();

            return redirect()->route('sales.index')
                ->with('success', 'Unpaid sale updated successfully! Invoice: ' . $sale->invoice_no);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Sale update failed: ' . $e->getMessage());
        }
    }


    /**
     * Mark a sale as paid (PATCH /sales/{id}/mark-paid)
     */
    public function markAsPaid(Request $request, Sale $sale)
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }
        $userRole = (int) ($user->getAttribute('role') ?? -1);
        $userDivisionId = $user->getAttribute('division_id');
        $saleDivisionId = $sale->getAttribute('division_id');

        // Cashiers can only update their own division's sales
        if ($userRole === 2 && $saleDivisionId && $saleDivisionId !== $userDivisionId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $sale->update(['paid_status' => 1]);

        return response()->json(['success' => true, 'sale_id' => $sale->id]);
    }

    /**
     * Return JSON list of unpaid sales (GET /sales/unpaid-list)
     * Scoped to cashier's division for role=2, all for admin.
     */
    public function unpaidList(Request $request)
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }
        $userRole = (int) ($user->getAttribute('role') ?? -1);
        $userDivisionId = $user->getAttribute('division_id');

        $sales = Sale::with('customer:id,name')
            ->select('id', 'invoice_no', 'customer_id', 'division_id', 'net_amount', 'sale_date', 'paid_status', 'payment2_type', 'payment2_amount')
            ->where('paid_status', 0)
            ->when($userRole === 2 && $userDivisionId, function ($q) use ($userDivisionId) {
                $q->where('division_id', $userDivisionId);
            })
            ->orderBy('sale_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'invoice_no' => $s->invoice_no,
                    'customer_name' => $s->customer?->name ?? 'Walk-in',
                    'net_amount' => number_format($s->net_amount, 2),
                    'sale_date' => $s->sale_date,
                ];
            });

        return response()->json($sales);
    }

    /**
     * Unpaid Sales Report page (GET /reports/unpaid-sales)
     */
    public function unpaidReport(Request $request)
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }
        $userRole = (int) ($user->getAttribute('role') ?? -1);
        $userDivisionId = $user->getAttribute('division_id');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $query = Sale::with('customer:id,name', 'user:id,name')
            ->select('id', 'invoice_no', 'customer_id', 'user_id', 'division_id', 'total_amount', 'discount', 'net_amount', 'sale_date', 'paid_status')
            ->where('paid_status', 0)
            ->when($userRole === 2 && $userDivisionId, function ($q) use ($userDivisionId) {
                $q->where('division_id', $userDivisionId);
            })
            ->when($startDate, fn($q) => $q->where('sale_date', '>=', $startDate))
            ->when($endDate,   fn($q) => $q->where('sale_date', '<=', $endDate))
            ->orderBy('sale_date', 'desc')
            ->orderBy('id', 'desc');

        $sales = $query->paginate(20)->withQueryString();
        $totalUnpaid = number_format($query->sum('net_amount'), 2);

        $sales->getCollection()->transform(function ($s) {
            return [
                'id' => $s->id,
                'invoice_no' => $s->invoice_no,
                'customer_name' => $s->customer?->name ?? 'Walk-in',
                'cashier' => $s->user?->name ?? 'N/A',
                'total_amount' => number_format($s->total_amount, 2),
                'discount' => number_format($s->discount, 2),
                'net_amount' => number_format($s->net_amount, 2),
                'sale_date' => $s->sale_date,
            ];
        });

        $currencySymbol = \App\Models\CompanyInformation::first();

        return \Inertia\Inertia::render('Reports/UnpaidSalesReport', [
            'sales'         => $sales,
            'totalUnpaid'   => $totalUnpaid,
            'startDate'     => $startDate,
            'endDate'       => $endDate,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    private function getPaymentTypeName($type)
    {
        return ['Cash', 'Card',
       // 'Credit'
        ][$type] ?? 'Unknown';
    }


        public function salesHistory()
        {
            $sales = Sale::with([
                    'customer',
                    'user',
                    'products.product',
                    'returns.products',
                    'returns.replacements',
                    // 'payments', // <--- Add payments relationship
                ])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $billSetting = BillSetting::latest('id')->first();

            $currencySymbol  = CompanyInformation::first();


            // compute return impact
            $salesTransformed = $sales->through(function ($sale) {
                $returnedTotal = $sale->returns->sum(function ($ret) {
                    if ($ret->return_type == \App\Models\SalesReturn::TYPE_CASH_RETURN) {
                        return (float) ($ret->refund_amount ?? 0);
                    }
                    return $ret->products->sum(fn($p) => (float) ($p->total ?? 0));
                });
                $netAfterReturn = max(0, ($sale->net_amount ?? 0) - $returnedTotal);

                return [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'type' => $sale->type,
                    'customer' => $sale->customer,
                    'products' => $sale->products,
                    'total_amount' => $sale->total_amount,
                    'discount' => $sale->discount,
                    'net_amount' => $sale->net_amount,
                    'balance' => $sale->balance,
                    'sale_date' => optional($sale->sale_date)->format('Y-m-d'),
                    'returns_count' => $sale->returns->count(),
                    'returns_total' => round($returnedTotal, 2),
                    'net_after_return' => round($netAfterReturn, 2),
                    // 'payments' => $sale->payments->map(function ($payment) {
                    //     return [
                    //         'payment_type' => $payment->payment_type,
                    //         'amount' => $payment->amount,
                    //     ];
                    // }),
                ];
            });


            return Inertia::render('Sales/AllSales', [
                'sales' => $salesTransformed,
                'billSetting' => $billSetting,
                'currencySymbol' => $currencySymbol,
            ]);
        }




}
