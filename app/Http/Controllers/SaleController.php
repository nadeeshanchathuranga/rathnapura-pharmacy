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
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
{

     public function index()
    {
        // Generate next invoice number
        $lastSale = Sale::latest('id')->first();
        $billSetting = BillSetting::latest('id')->first();

        $nextInvoiceNo = $lastSale ? 'INV-' . str_pad($lastSale->id + 1, 6, '0', STR_PAD_LEFT) : 'INV-000001';

        $products = Product::select('id', 'name', 'barcode', 'retail_price', 'wholesale_price', 'shop_quantity_in_sales_unit', 'shop_low_stock_margin', 'image', 'brand_id', 'category_id', 'type_id', 'discount_id', 'sales_unit_id', 'division_id')
            ->with(['brand:id,name', 'category:id,name', 'type:id,name', 'discount:id,name,value,type','salesUnit:id,name'])
            ->when(auth()->user()->role === 2 && auth()->user()->division_id, function ($q) {
                $q->where('division_id', auth()->user()->division_id);
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
        ]);
    }

    public function store(Request $request)
    {



        $request->validate([
            'invoice_no' => 'required|unique:sales,invoice_no',
            'customer_type' => 'required|in:retail,wholesale',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payments' => 'required|array|min:1',
            'payments.*.payment_type' => 'required|in:0,1,2',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.card_type' => 'nullable|in:visa,mastercard',
            'paid_status' => 'required|in:0,1',
        ]);

        foreach ($request->payments as $index => $payment) {
            if ((int) ($payment['payment_type'] ?? -1) === 1 && empty($payment['card_type'])) {
                return back()
                    ->withErrors(["payments.$index.card_type" => 'Card type is required for card payments.'])
                    ->withInput();
            }
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
            $totalPaid = collect($request->payments)->sum('amount');
            $balance = $netAmount - $totalPaid;

            // Convert customer_type to integer (1 = Retail, 2 = Wholesale)
            $type = $request->customer_type === 'wholesale' ? 2 : 1;

            // Determine second payment if two payment methods provided
            $secondPayment = isset($request->payments[1]) ? $request->payments[1] : null;

            // Create sale
            $sale = Sale::create([
                'invoice_no' => $request->invoice_no,
                'type' => $type,
                'customer_id' => $request->customer_id ?: null,
                'user_id' => Auth::id(),
                'division_id' => Auth::user()->division_id,
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
            $primaryPayment = $request->payments[0] ?? [];

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
     * Mark a sale as paid (PATCH /sales/{id}/mark-paid)
     */
    public function markAsPaid(Request $request, Sale $sale)
    {
        $user = Auth::user();

        // Cashiers can only update their own division's sales
        if ($user->role === 2 && $sale->division_id && $sale->division_id !== $user->division_id) {
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

        $sales = Sale::with('customer:id,name')
            ->select('id', 'invoice_no', 'customer_id', 'division_id', 'net_amount', 'sale_date', 'paid_status', 'payment2_type', 'payment2_amount')
            ->where('paid_status', 0)
            ->when($user->role === 2 && $user->division_id, function ($q) use ($user) {
                $q->where('division_id', $user->division_id);
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
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $query = Sale::with('customer:id,name', 'user:id,name')
            ->select('id', 'invoice_no', 'customer_id', 'user_id', 'division_id', 'total_amount', 'discount', 'net_amount', 'sale_date', 'paid_status')
            ->where('paid_status', 0)
            ->when($user->role === 2 && $user->division_id, function ($q) use ($user) {
                $q->where('division_id', $user->division_id);
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
