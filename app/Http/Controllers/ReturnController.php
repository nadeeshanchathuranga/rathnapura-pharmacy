<?php

namespace App\Http\Controllers;

use App\Models\SalesReturn;
use App\Models\SalesReturnProduct;
use App\Models\Sale;
use App\Models\SalesProduct;
use App\Models\Product;
use App\Models\ProductMovement;
use App\Models\CompanyInformation;
use App\Models\SalesReturnReplacementProduct;
use App\Models\Expense;
use App\Models\Income;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReturnController extends Controller
{
    /**
     * Display a listing of all returns and sales products available for return
     */
    public function index(Request $request)
    {

         
        $query = SalesReturn::with([
            'products.product' => function($query) {
                $query->select('id', 'name', 'barcode', 'return_product');
            },
            'replacements.product' => function($query) {
                $query->select('id', 'name', 'barcode', 'retail_price');
            },
            'sale' => function($query) {
                $query->select('id', 'invoice_no');
            },
            'customer' => function($query) {
                $query->select('id', 'name', 'phone_number');
            },
            'user' => function($query) {
                $query->select('id', 'name');
            }
        ]);

        // Filter by status if provided
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by date range if provided
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('return_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('return_date', '<=', $request->date_to);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('customer', function($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('phone_number', 'like', "%{$search}%");
                })
                ->orWhereHas('sale', function($query) use ($search) {
                    $query->where('invoice_no', 'like', "%{$search}%");
                })
                ->orWhereHas('products.product', function($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('barcode', 'like', "%{$search}%");
                });
            });
        }

        $returns = $query->orderBy('return_date', 'desc')
                        ->orderBy('id', 'desc')
                        ->paginate(15)
                        ->withQueryString();

              $currencySymbol  = CompanyInformation::first();           

        // Add computed fields
        $returns->through(function ($return) {
            // Compute returned and replacement totals for net payment
            $returnedTotal = $return->products
                ->map(function ($item) {
                    return (float) ($item->total ?? 0);
                })
                ->sum();
            $replacementTotal = $return->replacements
                ->map(function ($rep) {
                    $unit = $rep->unit_price ?? ($rep->product?->retail_price ?? 0);
                    return (float) $unit * (int) $rep->quantity;
                })
                ->sum();

            if ($return->return_type === SalesReturn::TYPE_CASH_RETURN) {
                $paymentDueLabel = 'Refund to customer';
                $paymentDueAmount = (float)($return->refund_amount ?? 0);
            } else {
                $diff = $replacementTotal - $returnedTotal;
                if ($diff > 0) {
                    $paymentDueLabel = 'Payment from customer';
                    $paymentDueAmount = $diff;
                } elseif ($diff < 0) {
                    $paymentDueLabel = 'Refund to customer';
                    $paymentDueAmount = abs($diff);
                } else {
                    $paymentDueLabel = 'Settled';
                    $paymentDueAmount = 0;
                }
            }
            return [
                'id' => $return->id,
                'return_no' => 'RET-' . str_pad($return->id, 5, '0', STR_PAD_LEFT),
                'sale_id' => $return->sale_id,
                'sale_no' => $return->sale?->invoice_no,
                'customer_id' => $return->customer_id,
                'customer_name' => $return->customer?->name,
                'customer_phone' => $return->customer?->phone_number,
                'user_name' => $return->user?->name,
                'return_date' => $return->return_date ? \Carbon\Carbon::parse($return->return_date)->format('Y-m-d') : null,
                'return_date_formatted' => $return->return_date ? \Carbon\Carbon::parse($return->return_date)->format('M d, Y') : 'N/A',
                'status' => $return->status,
                'status_text' => $return->status_text,
                'status_color' => $return->status_color,
                'return_type' => $return->return_type,
                'return_type_text' => $return->return_type_text,
                'return_type_color' => $return->return_type_color,
                'refund_amount' => $return->refund_amount,
                'refund_method' => $return->refund_method,
                'notes' => $return->notes,
                'total_refund' => $return->total_refund,
                'total_refund_formatted' => number_format($return->total_refund, 2),
                'returned_total' => $returnedTotal,
                'returned_total_formatted' => number_format($returnedTotal, 2),
                'replacement_total' => $replacementTotal,
                'replacement_total_formatted' => number_format($replacementTotal, 2),
                'payment_due_label' => $paymentDueLabel,
                'payment_due_amount' => $paymentDueAmount,
                'payment_due_formatted' => number_format($paymentDueAmount, 2),
                'products_count' => $return->products->count(),
                'returnable_products_count' => $return->products->filter(function($item) {
                    return $item->product?->return_product == true;
                })->count(),
                'return_products' => $return->products->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name,
                        'product_barcode' => $item->product?->barcode,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->total,
                        'is_returnable' => $item->product?->return_product == true,
                        'formatted_price' => number_format((float)$item->price, 2),
                        'formatted_total' => number_format((float)$item->total, 2),
                    ];
                }),
                'products' => $return->products->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name,
                        'product_barcode' => $item->product?->barcode,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->total,
                        'is_returnable' => $item->product?->return_product == true,
                        'formatted_price' => number_format((float)$item->price, 2),
                        'formatted_total' => number_format((float)$item->total, 2),
                    ];
                }),
            ];
        });

        // Get sales products available for return (last 30 days by default)
        $salesProducts = $this->getAvailableSalesProducts($request);

        return Inertia::render('Returns/Index', [
            'returns' => $returns,
            'salesProducts' => $salesProducts,
            'currencySymbol' => $currencySymbol,
            'billSetting' => \App\Models\BillSetting::latest('id')->first(),
            'shopProducts' => Product::select('id','name','barcode','shop_quantity_in_sales_unit','retail_price','wholesale_price')
                ->where('shop_quantity_in_sales_unit','>',0)
                ->orderBy('name')
                ->get(),
            'filters' => $request->only(['status', 'search', 'date_from', 'date_to']),
            'statusOptions' => [
                ['value' => SalesReturn::STATUS_PENDING, 'label' => 'Pending'],
                ['value' => SalesReturn::STATUS_APPROVED, 'label' => 'Approved'],
                ['value' => SalesReturn::STATUS_REJECTED, 'label' => 'Rejected'],
            ]
        ]);
    }

    /**
     * Update return status
     */
    public function updateStatus(Request $request, SalesReturn $return)
    {
        $request->validate([
            'status' => 'required|in:0,1,2'
        ]);

        DB::transaction(function () use ($request, $return) {
            $oldStatus = $return->status;
            $newStatus = $request->status;

            // Update return status
            $return->update([
                'status' => $newStatus
            ]);

            // If status changed from PENDING to APPROVED
            if ($oldStatus == SalesReturn::STATUS_PENDING && $newStatus == SalesReturn::STATUS_APPROVED) {
                
                // Handle Product Return (Type 1)
                if ($return->return_type == SalesReturn::TYPE_PRODUCT_RETURN) {
                    foreach ($return->products as $returnProduct) {
                        // Increase product quantity
                        $product = Product::find($returnProduct->product_id);
                        if ($product) {
                            $product->increment('shop_quantity_in_sales_unit', $returnProduct->quantity);
                            
                            // Record product movement (Sale Return - increases stock)
                            ProductMovement::recordMovement(
                                $returnProduct->product_id,
                                ProductMovement::TYPE_SALE_RETURN,
                                $returnProduct->quantity, // Positive for stock increase
                                'RETURN-' . $return->id . '-APPROVED'
                            );
                        }
                    }

                    // Issue replacement products (exchange) - decrease stock
                    foreach ($return->replacements as $rep) {
                        $product = Product::find($rep->product_id);
                        if ($product) {
                            $product->decrement('shop_quantity_in_sales_unit', $rep->quantity);
                            
                            // Record product movement (Release - reduces stock)
                            ProductMovement::recordMovement(
                                $rep->product_id,
                                ProductMovement::TYPE_SALE,
                                -$rep->quantity,
                                'RETURN-' . $return->id . '-REPLACEMENT'
                            );
                        }
                    }
                }
                
                // Handle Cash Return (Type 2)
                if ($return->return_type == SalesReturn::TYPE_CASH_RETURN) {
                    // Create expense record for cash refund
                    Expense::create([
                        'title' => 'Sales Return Refund - RET-' . str_pad($return->id, 5, '0', STR_PAD_LEFT),
                        'amount' => $return->refund_amount ?? $return->total_refund,
                        'remark' => 'Cash refund for return #' . $return->id . 
                                  ($return->sale ? ' (Invoice: ' . $return->sale->invoice_no . ')' : '') .
                                  ($return->notes ? ' - ' . $return->notes : ''),
                        'expense_date' => now(),
                        'payment_type' => $this->mapRefundMethodToPaymentType($return->refund_method),
                        'user_id' => Auth::id(),
                        'supplier_id' => null,
                        'reference' => 'SALES_RETURN_' . $return->id,
                    ]);
                }
            }

            // If status changed from APPROVED back to PENDING or REJECTED
            if ($oldStatus == SalesReturn::STATUS_APPROVED && $newStatus != SalesReturn::STATUS_APPROVED) {
                
                // Reverse Product Return (Type 1)
                if ($return->return_type == SalesReturn::TYPE_PRODUCT_RETURN) {
                    foreach ($return->products as $returnProduct) {
                        // Decrease product quantity (reverse the approval)
                        $product = Product::find($returnProduct->product_id);
                        if ($product) {
                            $product->decrement('shop_quantity_in_sales_unit', $returnProduct->quantity);
                            
                            // Record product movement (reverse)
                            ProductMovement::recordMovement(
                                $returnProduct->product_id,
                                ProductMovement::TYPE_SALE, // Use SALE type as reversal
                                -$returnProduct->quantity, // Negative for stock decrease
                                'RETURN-' . $return->id . '-REVERSED'
                            );
                        }
                    }

                    // Reverse replacement issuance (increase stock back)
                    foreach ($return->replacements as $rep) {
                        $product = Product::find($rep->product_id);
                        if ($product) {
                            $product->increment('shop_quantity_in_sales_unit', $rep->quantity);
                            
                            ProductMovement::recordMovement(
                                $rep->product_id,
                                ProductMovement::TYPE_SALE_RETURN,
                                $rep->quantity,
                                'RETURN-' . $return->id . '-REPLACEMENT-REVERSED'
                            );
                        }
                    }
                }
                
                // Reverse Cash Return (Type 2)
                if ($return->return_type == SalesReturn::TYPE_CASH_RETURN) {
                    // Delete the expense record
                    Expense::where('reference', 'SALES_RETURN_' . $return->id)->delete();
                }
            }
        });

        return back()->with('success', 'Return status updated successfully.');
    }

    /**
     * Map refund method to payment type
     */
    private function mapRefundMethodToPaymentType($refundMethod)
    {
        $mapping = [
            'cash' => 0,
            'card' => 1,
            'cheque' => 2,
            'bank_transfer' => 3,
        ];
        
        return $mapping[strtolower($refundMethod ?? 'cash')] ?? 0;
    }

    /**
     * Show return details
     */
    public function show(SalesReturn $return)
    {
        $return->load([
            'products.product',
            'sale',
            'customer',
            'user'
        ]);

        return Inertia::render('Returns/Show', [
            'return' => [
                'id' => $return->id,
                'sale_id' => $return->sale_id,
                'sale_no' => $return->sale?->invoice_no,
                'customer_id' => $return->customer_id,
                'customer_name' => $return->customer?->name,
                'customer_phone' => $return->customer?->phone_number,
                'user_name' => $return->user?->name,
                'return_date' => $return->return_date ? \Carbon\Carbon::parse($return->return_date)->format('Y-m-d') : null,
                'return_date_formatted' => $return->return_date ? \Carbon\Carbon::parse($return->return_date)->format('M d, Y') : 'N/A',
                'status' => $return->status,
                'status_text' => $return->status_text,
                'status_color' => $return->status_color,
                'return_type' => $return->return_type,
                'return_type_text' => $return->return_type_text,
                'return_type_color' => $return->return_type_color,
                'refund_amount' => $return->refund_amount,
                'refund_method' => $return->refund_method,
                'notes' => $return->notes,
                'total_refund' => number_format($return->total_refund, 2),
                'products' => $return->products->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name,
                        'product_barcode' => $item->product?->barcode,
                        'quantity' => $item->quantity,
                        'price' => number_format((float)$item->price, 2),
                        'total' => number_format((float)$item->total, 2),
                        'is_returnable' => $item->product?->return_product == true,
                    ];
                }),
            ]
        ]);
    }

    /**
     * Create a new return (if needed for future implementation)
     */
    public function store(Request $request)
    {
        $request->validate([
            'sale_id' => 'nullable|exists:sales,id',
            'customer_id' => 'nullable|exists:customers,id',
            'return_date' => 'required|date',
            'return_type' => 'required|in:1,2', // 1 = Product Return, 2 = Cash Return
            'refund_amount' => 'required_if:return_type,2|nullable|numeric|min:0',
            'refund_method' => 'required_if:return_type,2|nullable|string',
            'notes' => 'nullable|string|max:1000',
            'products' => 'required_if:return_type,1|array',
            'products.*.product_id' => 'required_with:products|exists:products,id',
            'products.*.quantity' => 'required_with:products|integer|min:1',
            'products.*.price' => 'required_with:products|numeric|min:0',
            'replacement_products' => 'nullable|array',
            'replacement_products.*.product_id' => 'required_with:replacement_products|exists:products,id',
            'replacement_products.*.quantity' => 'required_with:replacement_products|integer|min:1',
            'replacement_products.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        // Validate that all products are returnable for product returns
        if ($request->return_type == SalesReturn::TYPE_PRODUCT_RETURN && $request->products) {
            foreach ($request->products as $productData) {
                $product = Product::find($productData['product_id']);
                if (!$product->return_product) {
                    return back()->withErrors(['products' => "Product {$product->name} is not returnable."]);
                }
            }
        }

        DB::transaction(function () use ($request) {
            $return = SalesReturn::create([
                'sale_id' => $request->sale_id,
                'customer_id' => $request->customer_id,
                'user_id' => Auth::id(),
                'return_date' => $request->return_date,
                'return_type' => $request->return_type,
                'refund_amount' => $request->refund_amount,
                'refund_method' => $request->refund_method,
                'notes' => $request->notes,
                'status' => SalesReturn::STATUS_APPROVED,
            ]);

            // Only create product records for product returns
            if ($request->return_type == SalesReturn::TYPE_PRODUCT_RETURN && $request->products) {
                foreach ($request->products as $productData) {
                    $total = $productData['quantity'] * $productData['price'];
                    
                    SalesReturnProduct::create([
                        'sales_return_id' => $return->id,
                        'product_id' => $productData['product_id'],
                        'quantity' => $productData['quantity'],
                        'price' => $productData['price'],
                        'total' => $total,
                    ]);
                }

                // Optional replacement products (exchange)
                if (!empty($request->replacement_products)) {
                    foreach ($request->replacement_products as $rep) {
                        SalesReturnReplacementProduct::create([
                            'sales_return_id' => $return->id,
                            'product_id' => $rep['product_id'],
                            'quantity' => $rep['quantity'],
                            'unit_price' => $rep['unit_price'] ?? null,
                            'total' => isset($rep['unit_price']) ? ($rep['unit_price'] * $rep['quantity']) : null,
                        ]);
                    }
                }

                // Apply stock changes immediately: increase returned products
                foreach (SalesReturnProduct::where('sales_return_id', $return->id)->get() as $returnProduct) {
                    $product = Product::find($returnProduct->product_id);
                    if ($product) {
                        $product->increment('shop_quantity_in_sales_unit', $returnProduct->quantity);
                        ProductMovement::recordMovement(
                            $returnProduct->product_id,
                            ProductMovement::TYPE_SALE_RETURN,
                            $returnProduct->quantity,
                            'RETURN-' . $return->id
                        );
                        // Log to Shop Inventory
                        StoreInventoryController::logSalesReturn($returnProduct->product_id, $returnProduct->quantity, $return->id);
                    }
                }

                // Apply stock changes for replacements: decrease stock
                foreach (SalesReturnReplacementProduct::where('sales_return_id', $return->id)->get() as $rep) {
                    $product = Product::find($rep->product_id);
                    if ($product) {
                        $product->decrement('shop_quantity_in_sales_unit', $rep->quantity);
                        ProductMovement::recordMovement(
                            $rep->product_id,
                            ProductMovement::TYPE_SALE,
                            -$rep->quantity,
                            'RETURN-' . $return->id . '-REPLACEMENT'
                        );
                    }
                }
            }

            // --- Financial adjustments for product returns & cash refunds ---
            // Calculate return amount
            $returnAmount = 0;
            if ($request->return_type == SalesReturn::TYPE_PRODUCT_RETURN) {
                $returnAmount = SalesReturnProduct::where('sales_return_id', $return->id)
                    ->sum('total');
            } else {
                $returnAmount = $return->refund_amount ?? 0;
            }

            // Always create return Income record (will be subtracted from total income in reports)
            $transactionType = $request->return_type == SalesReturn::TYPE_PRODUCT_RETURN ? 'product_return' : 'cash_return';
            Income::create([
                'sale_id' => $return->sale_id,
                'source' => 'Sales Return - RET-' . str_pad($return->id, 5, '0', STR_PAD_LEFT),
                'amount' => $returnAmount,
                'income_date' => now()->format('Y-m-d'),
                'payment_type' => $request->return_type == SalesReturn::TYPE_CASH_RETURN ? ($this->mapRefundMethodToPaymentType($return->refund_method) ?? 0) : 0,
                'transaction_type' => $transactionType,
            ]);

            // If cash refund, immediately create expense
            if ($request->return_type == SalesReturn::TYPE_CASH_RETURN) {
                Expense::create([
                    'title' => 'Sales Return Refund - RET-' . str_pad($return->id, 5, '0', STR_PAD_LEFT),
                    'amount' => $return->refund_amount ?? 0,
                    'remark' => 'Cash refund for return #' . $return->id . ($return->notes ? ' - ' . $return->notes : ''),
                    'expense_date' => now(),
                    'payment_type' => $this->mapRefundMethodToPaymentType($return->refund_method),
                    'user_id' => Auth::id(),
                    'supplier_id' => null,
                    'reference' => 'SALES_RETURN_' . $return->id,
                ]);
            }

            // Get original sale to calculate discount rate
            $originalSale = Sale::find($return->sale_id);
            $discountRate = 0;
            if ($originalSale && $originalSale->total_amount > 0) {
                $discountRate = $originalSale->discount / $originalSale->total_amount;
            }
            
            // Record return entries into SalesProduct table with negative values
            foreach (SalesReturnProduct::where('sales_return_id', $return->id)->get() as $rp) {
                $returnQuantity = 0 - (int)$rp->quantity;
                $returnPrice = (float)($rp->price ?? 0);
                $returnTotal = $returnQuantity * $returnPrice;
                
                // Calculate negative discount (discount being removed)
                $returnDiscount = $returnTotal * $discountRate;
                $returnNet = $returnTotal - $returnDiscount;
                
                SalesProduct::create([
                    'sale_id'   => $return->sale_id,
                    'product_id'=> $rp->product_id,
                    'quantity'  => $returnQuantity,
                    'price'     => $returnPrice,
                    'total'     => $returnTotal,
                    'discount_amount' => round($returnDiscount, 2),
                    'net_amount' => round($returnNet, 2),
                    'is_return' => true,
                ]);
            }

            // Record replacement products with discount applied
            $totalReplacementNet = 0;
            foreach (SalesReturnReplacementProduct::where('sales_return_id', $return->id)->get() as $rep) {
                $replacementPrice = $rep->unit_price ?? (Product::find($rep->product_id)?->retail_price ?? 0);
                $replacementQuantity = (int)$rep->quantity;
                $replacementTotal = $replacementQuantity * $replacementPrice;
                
                // Apply same discount rate as original sale
                $replacementDiscount = $replacementTotal * $discountRate;
                $replacementNet = $replacementTotal - $replacementDiscount;
                $discountedUnitPrice = $replacementQuantity > 0 
                    ? $replacementNet / $replacementQuantity 
                    : $replacementPrice;
                
                SalesProduct::create([
                    'sale_id'   => $return->sale_id,
                    'product_id'=> $rep->product_id,
                    'quantity'  => $replacementQuantity,
                    'price'     => round($discountedUnitPrice, 2),
                    'total'     => $replacementTotal,
                    'discount_amount' => round($replacementDiscount, 2),
                    'net_amount' => round($replacementNet, 2),
                    'is_return' => false,
                ]);
                
                $totalReplacementNet += $replacementNet;
            }
            
            // Create Income record for replacement products in exchange
            if ($totalReplacementNet > 0) {
                Income::create([
                    'sale_id' => $return->sale_id,
                    'source' => 'Exchange Replacement - RET-' . str_pad($return->id, 5, '0', STR_PAD_LEFT),
                    'amount' => round($totalReplacementNet, 2),
                    'income_date' => now()->format('Y-m-d'),
                    'payment_type' => 0,
                    'transaction_type' => 'sale',
                ]);
            }
            
            // Recalculate and update Sale totals
            if ($originalSale) {
                $allProducts = SalesProduct::where('sale_id', $return->sale_id)
                    ->where('is_return', false)
                    ->where('quantity', '>', 0)
                    ->get();
                
                $newTotal = $allProducts->sum('total');
                $newDiscount = $allProducts->sum('discount_amount');
                $newNet = $allProducts->sum('net_amount');
                
                // Calculate the return amount for this specific return
                $currentReturnAmount = SalesReturnProduct::where('sales_return_id', $return->id)->sum('total');
                
                $originalSale->update([
                    'total_amount' => round($newTotal, 2),
                    'discount' => round($newDiscount, 2),
                    'net_amount' => round($newNet, 2),
                    'return_amount' => round(($originalSale->return_amount ?? 0) + $currentReturnAmount, 2),
                    'balance' => round($newNet - $originalSale->paid_amount, 2),
                ]);
            }
        });

        return redirect()->route('return.index')->with('success', 'Return created successfully.');
    }

    /**
     * Get sales products available for return
     */
    private function getAvailableSalesProducts(Request $request)
    {
        $query = SalesProduct::with([
            'sale' => function($query) {
                $query->select('id', 'invoice_no', 'sale_date', 'customer_id');
            },
            'sale.customer' => function($query) {
                $query->select('id', 'name', 'phone_number');
            },
            'product' => function($query) {
                $query->select('id', 'name', 'barcode', 'return_product');
            }
        ])
        ->whereHas('product', function($query) {
            $query->where('return_product', true);
        })
        ->whereHas('sale', function($query) {
            // Only sales from last 30 days by default
            $query->where('sale_date', '>=', now()->subDays(30));
        });

        // Filter by search if provided
        if ($request->has('sales_search') && $request->sales_search) {
            $search = $request->sales_search;
            $query->where(function($q) use ($search) {
                $q->whereHas('sale', function($query) use ($search) {
                    $query->where('invoice_no', 'like', "%{$search}%");
                })
                ->orWhereHas('sale.customer', function($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('phone_number', 'like', "%{$search}%");
                })
                ->orWhereHas('product', function($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('barcode', 'like', "%{$search}%");
                });
            });
        }

        // Filter by date range for sales if provided
        if ($request->has('sales_date_from') && $request->sales_date_from) {
            $query->whereHas('sale', function($q) use ($request) {
                $q->whereDate('sale_date', '>=', $request->sales_date_from);
            });
        }
        if ($request->has('sales_date_to') && $request->sales_date_to) {
            $query->whereHas('sale', function($q) use ($request) {
                $q->whereDate('sale_date', '<=', $request->sales_date_to);
            });
        }

        // Exclude products that have been fully returned for this sale/product
        $query->whereRaw('(
            (SELECT COALESCE(SUM(quantity),0) FROM sales_return_products srp WHERE srp.product_id = sales_products.product_id AND srp.sales_return_id IN (SELECT id FROM sales_return WHERE sale_id = sales_products.sale_id))
        ) < sales_products.quantity');

        $salesProducts = $query->orderBy('id', 'desc')
                              ->paginate(10, ['*'], 'sales_page')
                              ->withQueryString();

        return $salesProducts->through(function ($salesProduct) {
            return [
                'id' => $salesProduct->id,
                'sale_id' => $salesProduct->sale_id,
                'sale_no' => $salesProduct->sale?->invoice_no,
                'sale_date' => $salesProduct->sale?->sale_date ? \Carbon\Carbon::parse($salesProduct->sale->sale_date)->format('Y-m-d') : null,
                'sale_date_formatted' => $salesProduct->sale?->sale_date ? \Carbon\Carbon::parse($salesProduct->sale->sale_date)->format('M d, Y') : 'N/A',
                'customer_name' => $salesProduct->sale?->customer?->name ?? 'Walk-in Customer',
                'customer_phone' => $salesProduct->sale?->customer?->phone_number,
                'product_id' => $salesProduct->product_id,
                'product_name' => $salesProduct->product?->name,
                'product_barcode' => $salesProduct->product?->barcode,
                'quantity_sold' => $salesProduct->quantity,
                'price' => $salesProduct->price,
                'total' => $salesProduct->total,
                'is_returnable' => $salesProduct->product?->return_product == true,
                'formatted_price' => number_format((float)$salesProduct->price, 2),
                'formatted_total' => number_format((float)$salesProduct->total, 2),
                'can_return' => true, // Since we're filtering for returnable products
            ];
        });
    }

    /**
     * Create return from selected sales products
     */
    public function createFromSales(Request $request)
{
    $request->validate([
        'return_type' => 'required|in:1,2', // 1 = Product Return, 2 = Cash Return
        'refund_amount' => 'required_if:return_type,2|nullable|numeric|min:0',
        'refund_method' => 'required_if:return_type,2|nullable|string',
        'notes' => 'nullable|string|max:1000',
        'selected_products' => 'required|array|min:1',
        'selected_products.*.sales_product_id' => 'required|exists:sales_products,id',
        'selected_products.*.return_quantity' => 'required|integer|min:1',
        'replacement_products' => 'nullable|array',
        'replacement_products.*.product_id' => 'required_with:replacement_products|exists:products,id',
        'replacement_products.*.quantity' => 'required_with:replacement_products|integer|min:1',
        'replacement_products.*.unit_price' => 'nullable|numeric|min:0',
        // Multi-payment support
        'payments' => 'nullable|array',
        'payments.*.amount' => 'required_with:payments|numeric|min:0',
        'payments.*.method' => 'required_with:payments|string|in:cash,card,cheque,bank_transfer',
        'payments.*.card_type' => 'nullable|in:visa,mastercard',
    ]);

    foreach ($request->input('payments', []) as $index => $payment) {
        if (($payment['method'] ?? null) === 'card' && empty($payment['card_type'])) {
            throw ValidationException::withMessages([
                "payments.{$index}.card_type" => 'Card type is required when payment method is card.',
            ]);
        }
    }

    DB::transaction(function () use ($request) {
        // Get the first sales product to determine sale and customer
        $firstSalesProduct = SalesProduct::with(['sale', 'product'])->find($request->selected_products[0]['sales_product_id']);
        
        // Calculate total refund for product returns using DISCOUNTED PRICE
        $totalRefund = 0;
        if ($request->return_type == SalesReturn::TYPE_PRODUCT_RETURN) {
            foreach ($request->selected_products as $productData) {
                $salesProduct = SalesProduct::find($productData['sales_product_id']);
                
                // Calculate discounted unit price
                // If net_amount exists, use it to calculate per-unit discounted price
                if (isset($salesProduct->net_amount) && $salesProduct->quantity > 0) {
                    $discountedUnitPrice = $salesProduct->net_amount / $salesProduct->quantity;
                } else {
                    // Fallback: calculate from discount_amount
                    $unitDiscount = $salesProduct->quantity > 0 
                        ? ($salesProduct->discount_amount ?? 0) / $salesProduct->quantity 
                        : 0;
                    $discountedUnitPrice = $salesProduct->price - $unitDiscount;
                }
                
                $totalRefund += $discountedUnitPrice * $productData['return_quantity'];
            }
        } else {
            $totalRefund = $request->refund_amount;
        }
        
        $return = SalesReturn::create([
            'sale_id' => $firstSalesProduct->sale_id,
            'customer_id' => $firstSalesProduct->sale->customer_id,
            'user_id' => Auth::id(),
            'return_date' => now()->format('Y-m-d'),
            'return_type' => $request->return_type,
            'refund_amount' => $request->return_type == SalesReturn::TYPE_CASH_RETURN ? $request->refund_amount : $totalRefund,
            'refund_method' => $request->refund_method,
            'notes' => $request->notes,
            'status' => SalesReturn::STATUS_APPROVED,
        ]);

        foreach ($request->selected_products as $productData) {
            $salesProduct = SalesProduct::with('product')->find($productData['sales_product_id']);
            
            // Store original quantity before any updates
            $originalSoldQuantity = $salesProduct->quantity;
            
            // Validate total return quantity (including previous returns) doesn't exceed sold quantity
            $previousReturnedQty = SalesReturnProduct::whereHas('salesReturn', function($q) use ($salesProduct) {
                $q->where('sale_id', $salesProduct->sale_id);
            })
            ->where('product_id', $salesProduct->product_id)
            ->sum('quantity');

            $totalReturnQty = $previousReturnedQty + $productData['return_quantity'];
            if ($totalReturnQty > $originalSoldQuantity) {
                throw new \Exception("Total return quantity (including previous returns) cannot exceed sold quantity for {$salesProduct->product->name}. Already returned: $previousReturnedQty, trying to return: {$productData['return_quantity']}, sold: {$originalSoldQuantity}");
            }

            // Validate product is returnable (only for product returns)
            if ($request->return_type == SalesReturn::TYPE_PRODUCT_RETURN && !$salesProduct->product->return_product) {
                throw new \Exception("Product {$salesProduct->product->name} is not returnable");
            }

            // CRITICAL: Use the discounted price for return calculation
            // Calculate the per-unit price after discount from the original sale
            if (isset($salesProduct->net_amount) && $salesProduct->quantity > 0) {
                $returnPrice = $salesProduct->net_amount / $salesProduct->quantity;
            } else {
                // Fallback calculation
                $unitDiscount = $salesProduct->quantity > 0 
                    ? ($salesProduct->discount_amount ?? 0) / $salesProduct->quantity 
                    : 0;
                $returnPrice = $salesProduct->price - $unitDiscount;
            }
            
            // Calculate amounts being returned
            $returnTotal = $productData['return_quantity'] * $returnPrice;
            
            // Calculate proportional discount and subtotal being returned
            $unitDiscount = $salesProduct->quantity > 0 
                ? ($salesProduct->discount_amount ?? 0) / $salesProduct->quantity 
                : 0;
            $returnedDiscount = $unitDiscount * $productData['return_quantity'];
            $returnedSubtotal = $salesProduct->price * $productData['return_quantity'];
            
            SalesReturnProduct::create([
                'sales_return_id' => $return->id,
                'product_id' => $salesProduct->product_id,
                'quantity' => $productData['return_quantity'],
                'price' => round($returnPrice, 2),
                'total' => round($returnTotal, 2),
            ]);
            
            // Update the sales_product record: adjust quantity, net_amount, discount_amount, total, and price
            $salesProduct->quantity = $salesProduct->quantity - $productData['return_quantity'];
            if ($salesProduct->quantity < 0) {
                $salesProduct->quantity = 0;
            }
            
            // Reduce the line item amounts proportionally
            $salesProduct->discount_amount = max(0, $salesProduct->discount_amount - $returnedDiscount);
            $salesProduct->net_amount = max(0, $salesProduct->net_amount - $returnTotal);
            $salesProduct->total = max(0, $salesProduct->total - $returnedSubtotal);
            
            // Recalculate discounted unit price for remaining items
            if ($salesProduct->quantity > 0) {
                $salesProduct->price = round($salesProduct->net_amount / $salesProduct->quantity, 2);
            }
            
            $salesProduct->save();
            
            // Update the parent Sale record totals
            $sale = Sale::find($salesProduct->sale_id);
            if ($sale) {
                $sale->total_amount = max(0, $sale->total_amount - $returnedSubtotal);
                $sale->discount = max(0, $sale->discount - $returnedDiscount);
                $sale->net_amount = max(0, $sale->net_amount - $returnTotal);
                
                // Track return amount separately
                $sale->return_amount = ($sale->return_amount ?? 0) + $returnTotal;
                
                // Recalculate balance: (net_amount - paid_amount)
                // When return happens, net_amount decreases, so balance becomes more negative (we owe customer more)
                $sale->balance = $sale->net_amount - $sale->paid_amount;
                
                $sale->save();
            }
        }

        // Store replacement products for exchange
        if (!empty($request->replacement_products)) {
            foreach ($request->replacement_products as $rep) {
                SalesReturnReplacementProduct::create([
                    'sales_return_id' => $return->id,
                    'product_id' => $rep['product_id'],
                    'quantity' => $rep['quantity'],
                    'unit_price' => $rep['unit_price'] ?? null,
                    'total' => isset($rep['unit_price']) ? ($rep['unit_price'] * $rep['quantity']) : null,
                ]);
            }
        }

        // Apply stock changes immediately for product returns
        if ($request->return_type == SalesReturn::TYPE_PRODUCT_RETURN) {
            foreach (SalesReturnProduct::where('sales_return_id', $return->id)->get() as $returnProduct) {
                $product = Product::find($returnProduct->product_id);
                if ($product) {
                    $product->increment('shop_quantity_in_sales_unit', $returnProduct->quantity);
                    ProductMovement::recordMovement(
                        $returnProduct->product_id,
                        ProductMovement::TYPE_SALE_RETURN,
                        $returnProduct->quantity,
                        'RETURN-' . $return->id
                    );
                }
            }

            foreach (SalesReturnReplacementProduct::where('sales_return_id', $return->id)->get() as $rep) {
                $product = Product::find($rep->product_id);
                if ($product) {
                    $product->decrement('shop_quantity_in_sales_unit', $rep->quantity);
                    ProductMovement::recordMovement(
                        $rep->product_id,
                        ProductMovement::TYPE_SALE,
                        -$rep->quantity,
                        'RETURN-' . $return->id . '-REPLACEMENT'
                    );
                }
            }
        }

        // Apply stock changes for cash refunds: increase returned products
        if ($request->return_type == SalesReturn::TYPE_CASH_RETURN) {
            foreach (SalesReturnProduct::where('sales_return_id', $return->id)->get() as $returnProduct) {
                $product = Product::find($returnProduct->product_id);
                if ($product) {
                    $product->increment('shop_quantity_in_sales_unit', $returnProduct->quantity);
                    ProductMovement::recordMovement(
                        $returnProduct->product_id,
                        ProductMovement::TYPE_SALE_RETURN,
                        $returnProduct->quantity,
                        'RETURN-' . $return->id
                    );
                }
            }
        }

        // Cash refund: create expense immediately
        // if ($request->return_type == SalesReturn::TYPE_CASH_RETURN) {
        //     Expense::create([
        //         'title' => 'Sales Return Refund - RET-' . str_pad($return->id, 5, '0', STR_PAD_LEFT),
        //         'amount' => $return->refund_amount ?? 0,
        //         'remark' => 'Cash refund for return #' . $return->id . ($return->notes ? ' - ' . $return->notes : ''),
        //         'expense_date' => now(),
        //         'payment_type' => $this->mapRefundMethodToPaymentType($return->refund_method),
        //         'user_id' => Auth::id(),
        //         'supplier_id' => null,
        //         'reference' => 'SALES_RETURN_' . $return->id,
        //     ]);
        // }

        // Always create return Income record (will be subtracted from total income in reports)
        // For exchanges, replacement Income will be created separately (added to income)
        $returnAmount = $return->return_type == SalesReturn::TYPE_PRODUCT_RETURN ? $totalRefund : $request->refund_amount;
        $transactionType = $return->return_type == SalesReturn::TYPE_PRODUCT_RETURN ? 'product_return' : 'cash_return';
        
        Income::create([
            'sale_id' => $firstSalesProduct->sale_id,
            'source' => 'Sales Return - RET-' . str_pad($return->id, 5, '0', STR_PAD_LEFT),
            'amount' => $returnAmount,
            'income_date' => now()->format('Y-m-d'),
            'payment_type' => $return->return_type == SalesReturn::TYPE_CASH_RETURN ? ($this->mapRefundMethodToPaymentType($return->refund_method) ?? 0) : 0,
            'transaction_type' => $transactionType,
        ]);

        // --- Record entries into SalesProduct table ---
        // Get original sale to calculate discount rate
        $originalSale = Sale::find($firstSalesProduct->sale_id);
        $discountRate = 0;
        if ($originalSale && $originalSale->total_amount > 0) {
            $discountRate = $originalSale->discount / $originalSale->total_amount;
        }
        
        // Create negative quantity entries for returned items
        foreach (SalesReturnProduct::where('sales_return_id', $return->id)->get() as $rp) {
            $returnQuantity = 0 - (int)$rp->quantity;
            $returnPrice = (float)($rp->price ?? 0);
            $returnTotal = $returnQuantity * $returnPrice;
            
            // Calculate negative discount (discount being removed)
            $returnDiscount = $returnTotal * $discountRate;
            $returnNet = $returnTotal - $returnDiscount;
            
            SalesProduct::create([
                'sale_id'   => $firstSalesProduct->sale_id,
                'product_id'=> $rp->product_id,
                'quantity'  => $returnQuantity,
                'price'     => $returnPrice,
                'total'     => $returnTotal,
                'discount_amount' => round($returnDiscount, 2),
                'net_amount' => round($returnNet, 2),
                'is_return' => true,
            ]);
        }

        // Create positive quantity entries for replacement products with discount applied
        $totalReplacementNet = 0;
        foreach (SalesReturnReplacementProduct::where('sales_return_id', $return->id)->get() as $rep) {
            $replacementPrice = $rep->unit_price ?? (Product::find($rep->product_id)?->retail_price ?? 0);
            $replacementQuantity = (int)$rep->quantity;
            $replacementTotal = $replacementQuantity * $replacementPrice;
            
            // Apply same discount rate as original sale
            $replacementDiscount = $replacementTotal * $discountRate;
            $replacementNet = $replacementTotal - $replacementDiscount;
            $discountedUnitPrice = $replacementQuantity > 0 
                ? $replacementNet / $replacementQuantity 
                : $replacementPrice;
            
            SalesProduct::create([
                'sale_id'   => $firstSalesProduct->sale_id,
                'product_id'=> $rep->product_id,
                'quantity'  => $replacementQuantity,
                'price'     => round($discountedUnitPrice, 2),
                'total'     => $replacementTotal,
                'discount_amount' => round($replacementDiscount, 2),
                'net_amount' => round($replacementNet, 2),
                'is_return' => false,
            ]);
            
            $totalReplacementNet += $replacementNet;
        }
        
        // Create Income record for replacement products in exchange
        if ($totalReplacementNet > 0) {
            Income::create([
                'sale_id' => $firstSalesProduct->sale_id,
                'source' => 'Exchange Replacement - RET-' . str_pad($return->id, 5, '0', STR_PAD_LEFT),
                'amount' => round($totalReplacementNet, 2),
                'income_date' => now()->format('Y-m-d'),
                'payment_type' => 0,
                'transaction_type' => 'sale',
            ]);
        }
        
        // Recalculate and update Sale totals after returns and replacements
        if ($originalSale) {
            $allProducts = SalesProduct::where('sale_id', $firstSalesProduct->sale_id)
                ->where('is_return', false)
                ->where('quantity', '>', 0)
                ->get();
            
            $newTotal = $allProducts->sum('total');
            $newDiscount = $allProducts->sum('discount_amount');
            $newNet = $allProducts->sum('net_amount');
            
            // Calculate the return amount for this specific return
            $currentReturnAmount = SalesReturnProduct::where('sales_return_id', $return->id)->sum('total');
            
            $originalSale->update([
                'total_amount' => round($newTotal, 2),
                'discount' => round($newDiscount, 2),
                'net_amount' => round($newNet, 2),
                'return_amount' => round(($originalSale->return_amount ?? 0) + $currentReturnAmount, 2),
                'balance' => round($newNet - $originalSale->paid_amount, 2),
            ]);
        }
    });

    return back()->with('success', 'Return created successfully from selected products.');
}

    /**
     * Update an existing return
     */
    public function update(Request $request, SalesReturn $return)
    {
        // Only allow updates to pending returns
        if ($return->status != SalesReturn::STATUS_PENDING) {
            return back()->withErrors(['error' => 'Only pending returns can be edited.']);
        }

        $request->validate([
            'status' => 'nullable|in:0,1,2',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $return) {
            // If status was APPROVED, reverse the stock movements before updating
            if ($return->status == SalesReturn::STATUS_APPROVED) {
                foreach ($return->products as $oldProduct) {
                    $product = Product::find($oldProduct->product_id);
                    if ($product) {
                        $product->decrement('shop_quantity_in_sales_unit', $oldProduct->quantity);
                        
                        // Record product movement (reverse)
                        ProductMovement::recordMovement(
                            $oldProduct->product_id,
                            ProductMovement::TYPE_SALE,
                            -$oldProduct->quantity,
                            'RETURN-' . $return->id . '-UPDATED-REVERSED'
                        );
                    }
                }
            }

            // Delete old products
            SalesReturnProduct::where('sales_return_id', $return->id)->delete();

            // Create new products
            foreach ($request->products as $productData) {
                $product = Product::find($productData['product_id']);
                
                // Validate product is returnable
                if (!$product->return_product) {
                    throw new \Exception("Product {$product->name} is not returnable");
                }

                $total = $productData['quantity'] * $productData['price'];
                
                SalesReturnProduct::create([
                    'sales_return_id' => $return->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                    'total' => $total,
                ]);
            }

            // Update return status if provided
            if ($request->has('status')) {
                $return->update(['status' => $request->status]);

                // If new status is APPROVED, increase stock
                if ($request->status == SalesReturn::STATUS_APPROVED) {
                    foreach ($request->products as $productData) {
                        $product = Product::find($productData['product_id']);
                        if ($product) {
                            $product->increment('shop_quantity_in_sales_unit', $productData['quantity']);
                            
                            // Record product movement
                            ProductMovement::recordMovement(
                                $productData['product_id'],
                                ProductMovement::TYPE_SALE_RETURN,
                                $productData['quantity'],
                                'RETURN-' . $return->id . '-UPDATED'
                            );
                        }
                    }
                }
            }
        });

        return back()->with('success', 'Return updated successfully.');
    }

    /**
     * Delete a return
     */
    public function destroy(SalesReturn $return)
    {
        // Only allow deletion of pending returns
        if ($return->status != SalesReturn::STATUS_PENDING) {
            return back()->withErrors(['error' => 'Only pending returns can be deleted.']);
        }

        DB::transaction(function () use ($return) {
            // If return was approved, reverse the stock movements
            if ($return->status == SalesReturn::STATUS_APPROVED) {
                foreach ($return->products as $returnProduct) {
                    $product = Product::find($returnProduct->product_id);
                    if ($product) {
                        $product->decrement('shop_quantity_in_sales_unit', $returnProduct->quantity);
                        
                        // Record product movement (reverse)
                        ProductMovement::recordMovement(
                            $returnProduct->product_id,
                            ProductMovement::TYPE_SALE,
                            -$returnProduct->quantity,
                            'RETURN-' . $return->id . '-DELETED'
                        );
                    }
                }
            }

            // Delete return products (cascading delete)
            SalesReturnProduct::where('sales_return_id', $return->id)->delete();
            
            // Delete the return
            $return->delete();
        });

        return back()->with('success', 'Return deleted successfully.');
    }

    /**
     * Export Sales Return Bill as PDF
     */
    public function exportBillPdf(SalesReturn $return)
    {
        $return->load([
            'products.product',
            'replacements.product',
            'sale',
            'customer',
            'user'
        ]);

        $company = CompanyInformation::first();
        $currency = $company?->currency ?? '';

        $returnedTotal = $return->products
            ->map(function ($item) {
                return (float) ($item->total ?? 0);
            })
            ->sum();

        $replacementTotal = $return->replacements
            ->map(function ($rep) {
                $unit = $rep->unit_price ?? ($rep->product?->retail_price ?? 0);
                return (float) $unit * (int) $rep->quantity;
            })
            ->sum();

        if ($return->return_type === SalesReturn::TYPE_CASH_RETURN) {
            $netAmount = (float)($return->refund_amount ?? 0);
            $balanceLabel = 'Refund to customer';
        } else {
            $netAmount = $returnedTotal - $replacementTotal;
            $balanceLabel = $netAmount >= 0 ? 'Balance to customer' : 'Balance from customer';
        }

        $data = [
            'company' => $company,
            'currency' => $currency,
            'return' => $return,
            'returnedTotal' => $returnedTotal,
            'replacementTotal' => $replacementTotal,
            'netAmount' => $netAmount,
            'netDisplayAmount' => abs($netAmount),
            'balanceLabel' => $balanceLabel,
            'returnNo' => 'RET-' . str_pad($return->id, 5, '0', STR_PAD_LEFT),
        ];

        $pdf = Pdf::loadView('reports.Components.sales-return-pdf', $data);
        return $pdf->download('sales-return-' . $data['returnNo'] . '.pdf');
    }
}