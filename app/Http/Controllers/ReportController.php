<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Income;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Expense;
use App\Models\Supplier;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\GoodsReceivedNoteReturn;
use App\Models\CompanyInformation;
use App\Models\ProductMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
// use Maatwebsite\Excel\Facades\Excel;
// use App\Exports\SalesReportExport;
// use App\Exports\ProductStockExport;
// use App\Exports\ExpensesReportExport;

/**
 * ReportController
 *
 * Handles all reporting functionality for the JPOS system including:
 * - Income and sales reports with payment type breakdown
 * - Product stock and movement tracking
 * - Expense reports
 * - GRN (Goods Received Note) and GRN Return reports
 * - Export functionality (PDF and Excel/CSV)
 *
 * @package App\Http\Controllers
 */
class ReportController extends Controller
{
    private function shouldScopeToCashierDivision(): bool
    {
        $user = auth()->user();

        return $user && (int) $user->role === 2 && !empty($user->division_id);
    }

    private function cashierDivisionId(): ?int
    {
        if (!$this->shouldScopeToCashierDivision()) {
            return null;
        }

        return (int) auth()->user()->division_id;
    }

    private function applyCashierDivisionFilterToProducts($query)
    {
        $divisionId = $this->cashierDivisionId();

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        }

        return $query;
    }

    private function applyCashierDivisionFilterToIncome($query)
    {
        $divisionId = $this->cashierDivisionId();

        if ($divisionId) {
            $query->whereHas('sale', function ($saleQuery) use ($divisionId) {
                $saleQuery->where('division_id', $divisionId);
            });
        }

        return $query;
    }

    /**
     * Display the main reports dashboard
     *
     * Provides a comprehensive overview of business operations including:
     * - Income summary by payment type (Cash, Card, Credit)
     * - Sales summary by type (Retail, Wholesale) with returns adjustment
     * - Product stock levels
     * - Expense summary
     *
     * @param Request $request - Contains optional start_date and end_date filters
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        // Get date range from request or default to current month
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // Income summary by payment type
        $incomeSummary = Income::select(
                'payment_type',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->whereBetween('income_date', [$startDate, $endDate])
            ->groupBy('payment_type')
            ->get()
            ->map(function ($item) {
                $paymentTypes = ['Cash', 'Card', 'Credit'];
                return [
                    'payment_type' => $item->payment_type,
                    'payment_type_name' => $paymentTypes[$item->payment_type] ?? 'Unknown',
                    'total_amount' => number_format($item->total_amount, 2),
                    'transaction_count' => $item->transaction_count,
                ];
            });

        // Total income for the period
        $totalIncome = Income::whereBetween('income_date', [$startDate, $endDate])
            ->sum('amount');

        // Detailed income list for the view (avoid undefined variable)
        $incomeList = Income::with(['user:id,name'])
            ->select('id', 'amount', 'income_date', 'payment_type', 'reference', 'user_id', 'description')
            ->whereBetween('income_date', [$startDate, $endDate])
            ->orderBy('income_date', 'desc')
            ->get()
            ->map(function ($item) {
                $paymentTypes = [0 => 'Cash', 1 => 'Card', 2 => 'Credit'];
                return [
                    'id' => $item->id,
                    'amount' => number_format($item->amount, 2),
                    'income_date' => $item->income_date,
                    'payment_type' => $item->payment_type,
                    'payment_type_name' => $paymentTypes[$item->payment_type] ?? 'Unknown',
                    'reference' => $item->reference ?? null,
                    'description' => $item->description ?? null,
                    'user_name' => $item->user->name ?? 'N/A',
                ];
            });

        // Sales summary with returns adjustment
        $salesSummary = Sale::select(
                'type',
                DB::raw('COUNT(*) as total_sales'),
                DB::raw('SUM(total_amount) as gross_total'),
                DB::raw('SUM(discount) as total_discount'),
                DB::raw('SUM(net_amount) as net_total'),
                DB::raw('SUM(balance) as total_balance')
            )
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->groupBy('type')
            ->get()
            ->map(function ($item) use ($startDate, $endDate) {
                $types = [1 => 'Retail', 2 => 'Wholesale'];

                // Calculate total approved returns for this sale type
                $totalReturns = DB::table('sales_return')
                    ->join('sales', 'sales_return.sale_id', '=', 'sales.id')
                    ->join('sales_return_products', 'sales_return.id', '=', 'sales_return_products.sales_return_id')
                    ->where('sales.type', $item->type)
                    ->where('sales_return.status', 1) // Only approved returns
                    ->whereBetween('sales.sale_date', [$startDate, $endDate])
                    ->sum('sales_return_products.total');

                $grossTotal = $item->gross_total;
                $netTotal = $item->net_total;
                $netTotalAfterReturns = $netTotal - $totalReturns;

                return [
                    'type' => $item->type,
                    'type_name' => $types[$item->type] ?? 'Unknown',
                    'total_sales' => $item->total_sales,
                    'gross_total' => number_format($grossTotal, 2),
                    'total_discount' => number_format($item->total_discount, 2),
                    'net_total' => number_format($netTotal, 2),
                    'total_returns' => number_format($totalReturns, 2),
                    'net_total_after_returns' => number_format($netTotalAfterReturns, 2),
                    'total_balance' => number_format($item->total_balance, 2),
                ];
            });

        // Total sales count
        $totalSalesCount = Sale::whereBetween('sale_date', [$startDate, $endDate])->count();


           $currencySymbol  = CompanyInformation::first();
        // Products stock summary
        $productsStock = Product::select('id', 'name',   'qty', 'retail_price', 'wholesale_price')
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,

                    'stock' => $item->qty,
                    'retail_price' => number_format($item->retail_price, 2),
                    'wholesale_price' => number_format($item->wholesale_price, 2),
                    'stock_status' => $item->qty == 0 ? 'Out of Stock' : ($item->qty < 10 ? 'Low Stock' : 'In Stock'),
                ];
            });

        // Expenses summary by payment type
        $expensesSummary = Expense::select(
                'payment_type',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->groupBy('payment_type')
            ->get()
            ->map(function ($item) {
                $paymentTypes = [0 => 'Cash', 1 => 'Card', 2 => 'Credit'];
                return [
                    'payment_type' => $item->payment_type,
                    'payment_type_name' => $paymentTypes[$item->payment_type] ?? 'Unknown',
                    'total_amount' => number_format($item->total_amount, 2),
                    'transaction_count' => $item->transaction_count,
                ];
            });

        // Total expenses for the period
        $totalExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');

        // Expenses list with relations
        $expensesList = Expense::with(['user:id,name', 'supplier:id,name'])
            ->select('id', 'title', 'amount', 'remark', 'expense_date', 'payment_type', 'card_type', 'user_id', 'supplier_id', 'reference')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->orderBy('expense_date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'remark' => $item->remark,
                    'amount' => number_format($item->amount, 2),
                    'expense_date' => $item->expense_date,
                    'payment_type' => $item->payment_type,
                    'payment_type_name' => $this->getExpensePaymentTypeName($item->payment_type, $item->card_type),
                    'reference' => $item->reference,
                    'user_name' => $item->user->name ?? 'N/A',
                    'supplier_name' => $item->supplier->name ?? 'N/A',
                ];
            });

        // Product-wise Sales and Returns Report
        $productSalesReport = Product::select('id', 'name', 'barcode')
            ->with([
                'salesProducts' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'quantity', 'price', 'total', 'sale_id')
                        ->whereHas('sale', function($q) use ($startDate, $endDate) {
                            $q->whereBetween('sale_date', [$startDate, $endDate]);
                        });
                },
                'returnProducts' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'quantity', 'price', 'total', 'sales_return_id')
                        ->whereHas('salesReturn', function($q) use ($startDate, $endDate) {
                            $q->whereBetween('return_date', [$startDate, $endDate])
                              ->where('status', 1); // Only approved returns
                        });
                }
            ])
            ->get()
            ->map(function ($product) {
                $totalSalesQty = $product->salesProducts->sum('quantity');
                $totalSalesAmount = $product->salesProducts->sum('total');
                $totalReturnsQty = $product->returnProducts->sum('quantity');
                $totalReturnsAmount = $product->returnProducts->sum('total');
                $netSalesQty = $totalSalesQty - $totalReturnsQty;
                $netSalesAmount = $totalSalesAmount - $totalReturnsAmount;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'sales_quantity' => $totalSalesQty,
                    'sales_amount' => number_format($totalSalesAmount, 2),
                    'returns_quantity' => $totalReturnsQty,
                    'returns_amount' => number_format($totalReturnsAmount, 2),
                    'net_sales_quantity' => $netSalesQty,
                    'net_sales_amount' => number_format($netSalesAmount, 2),
                ];
            })
            ->filter(function ($item) {
                // Only show products that have sales or returns
                return $item['sales_quantity'] > 0 || $item['returns_quantity'] > 0;
            })
            ->values();

        return Inertia::render('Reports/Index', [
            'incomeSummary' => $incomeSummary,
            'salesSummary' => $salesSummary,
            'productsStock' => $productsStock,
            'expensesSummary' => $expensesSummary,
            'expensesList' => $expensesList,
            'productSalesReport' => $productSalesReport,
            'totalIncome' => number_format($totalIncome, 2),
            'totalExpenses' => number_format($totalExpenses, 2),
            'totalSalesCount' => $totalSalesCount,
            'currencySymbol' => $currencySymbol,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Export main dashboard report as PDF
     *
     * Generates a comprehensive PDF report containing income, sales,
     * and expense summaries for the specified date range.
     *
     * @param Request $request - Contains start_date and end_date parameters
    * @return \Symfony\Component\HttpFoundation\Response PDF download
     */
   public function exportPdf(Request $request)
{
    $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
    $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

    // Get currency from company information
    $currency = CompanyInformation::first()?->currency ?? 'Rs.';

    // Fetch detailed sales
    $sales = Sale::select('id', 'sale_date', 'type', 'total_amount', 'discount', 'net_amount', 'balance')
        ->whereBetween('sale_date', [$startDate, $endDate])
        ->orderBy('sale_date', 'desc')
        ->get();

    // Check if PDF class exists
    if (!class_exists(Pdf::class)) {
        return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
    }

    $pdf = Pdf::loadView('reports.Components.sales-pdf', [
        'sales' => $sales,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'currency' => $currency,
    ]);

    return $pdf->download('sales-report-' . date('Y-m-d') . '.pdf');
}

    /**
     * Export main dashboard report as Excel/CSV
     *
     * Streams a CSV file containing detailed breakdown of:
     * - Income by payment type
     * - Sales by type with returns
     * - Expense summary
     *
     * @param Request $request - Contains start_date and end_date parameters
    * @return \Symfony\Component\HttpFoundation\Response CSV stream download
     */
    public function exportExcel(Request $request)
    {

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $sales = Sale::select('id', 'sale_date', 'type', 'total_amount', 'discount', 'net_amount')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->orderBy('sale_date', 'desc')
            ->get();

        $currency = $request->input('currency', '');
        $filename = 'sales-report-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Sale Date','Type','Total Amount','Discount','Net Amount'];

        $callback = function() use ($sales, $columns, $currency) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            // 1 = Retail, 2 = Wholesale
            $typeLabels = [1 => 'Retail', 2 => 'Wholesale'];
            foreach ($sales as $s) {
                fputcsv($file, [
                    $s->sale_date,
                    $typeLabels[$s->type] ?? $s->type,
                    ($currency ? $currency . ' ' : '') . $s->total_amount,
                    ($currency ? $currency . ' ' : '') . $s->discount,
                    ($currency ? $currency . ' ' : '') . $s->net_amount,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export product stock report as PDF
     *
     * Generates a PDF showing current stock levels for all products
     * with retail and wholesale pricing information.
     *
    * @return \Symfony\Component\HttpFoundation\Response PDF download
     */
    public function exportProductStockPdf()
    {
        // Commented out - requires barryvdh/laravel-dompdf package
        /*
        $productsStock = Product::select('id', 'name',   'qty', 'retail_price', 'wholesale_price')
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('reports.Components.product-stock-pdf', [
            'productsStock' => $productsStock,
            'reportDate' => date('Y-m-d'),
        ]);

        return $pdf->download('product-stock-report-' . date('Y-m-d') . '.pdf');
        */
        $productQuery = Product::with(['purchaseUnit:id,symbol,name', 'transferUnit:id,symbol,name', 'salesUnit:id,symbol,name']);
        $this->applyCashierDivisionFilterToProducts($productQuery);

        $productsStock = $productQuery
            ->orderBy('name')
            ->get()
            ->map(function($p) {
                // Calculate loose bundles (store_quantity_in_transfer_unit is total, loose is the remainder)
                $purchaseQty = $p->store_quantity_in_purchase_unit ?? 0;
                $rate = $p->purchase_to_transfer_rate ?? 1;
                $totalBundles = $purchaseQty * $rate;
                $looseBundles = ($p->store_quantity_in_transfer_unit ?? 0) - $totalBundles;
                $looseBundles = $looseBundles < 0 ? 0 : $looseBundles;
                return [
                    'name' => $p->name,
                    'shop_qty_display' => $p->shop_quantity_in_sales_unit . ' ' . ($p->salesUnit->symbol ?? $p->salesUnit->name ?? ''),
                    'store_qty_display' => $p->store_quantity_in_purchase_unit . ' ' . ($p->purchaseUnit->symbol ?? $p->purchaseUnit->name ?? ''),
                    'loose_bundles' => $looseBundles . ' ' . ($p->transferUnit->symbol ?? $p->transferUnit->name ?? ''),
                ];
            });

        $currency = CompanyInformation::first()?->currency ?? 'Rs.';

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('reports.Components.product-stock-pdf', [
                'productsStock' => $productsStock,
                'reportDate' => date('Y-m-d'),
                'currency' => $currency,
            ]);
            return $pdf->download('product-stock-report-' . date('Y-m-d') . '.pdf');
        }

        return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
    }

    /**
     * Export product stock report as Excel/CSV
     *
     * Streams a CSV file with columns:
     * Product Name, Quantity, Retail Price, Wholesale Price
     *
    * @return \Symfony\Component\HttpFoundation\Response CSV stream download
     */
    public function exportProductStockExcel()
    {
        $productQuery = Product::with(['purchaseUnit:id,symbol,name', 'transferUnit:id,symbol,name', 'salesUnit:id,symbol,name']);
        $this->applyCashierDivisionFilterToProducts($productQuery);

        $productsStock = $productQuery
            ->orderBy('name')
            ->get()
            ->map(function($p) {
                $purchaseQty = $p->store_quantity_in_purchase_unit ?? 0;
                $rate = $p->purchase_to_transfer_rate ?? 1;
                $totalBundles = $purchaseQty * $rate;
                $looseBundles = ($p->store_quantity_in_transfer_unit ?? 0) - $totalBundles;
                $looseBundles = $looseBundles < 0 ? 0 : $looseBundles;
                return [
                    'name' => $p->name,
                    'shop_qty_display' => $p->shop_quantity_in_sales_unit . ' ' . ($p->salesUnit->symbol ?? $p->salesUnit->name ?? ''),
                    'store_qty_display' => $p->store_quantity_in_purchase_unit . ' ' . ($p->purchaseUnit->symbol ?? $p->purchaseUnit->name ?? ''),
                    'loose_bundles' => $looseBundles . ' ' . ($p->transferUnit->symbol ?? $p->transferUnit->name ?? ''),
                ];
            });

        $filename = 'product-stock-report-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Product Name','Shop Qty','Store Qty','Loose'];

        $callback = function() use ($productsStock, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($productsStock as $p) {
                fputcsv($file, [
                    $p['name'],
                    $p['shop_qty_display'],
                    $p['store_qty_display'],
                    $p['loose_bundles'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export expenses report as PDF
     *
     * Generates a detailed PDF of all expenses within the specified
     * date range, including category breakdown and totals.
     *
     * @param Request $request - Contains start_date and end_date parameters
    * @return \Symfony\Component\HttpFoundation\Response PDF download
     */
    public function exportExpensesPdf(Request $request)
    {
        // Commented out - requires barryvdh/laravel-dompdf package
        /*
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $expensesList = Expense::with(['user:id,name', 'supplier:id,name'])
            ->select('id', 'title', 'amount', 'remark', 'expense_date', 'payment_type', 'user_id', 'supplier_id', 'reference')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->orderBy('expense_date', 'desc')
            ->get();

        $totalExpenses = $expensesList->sum('amount');

        $pdf = Pdf::loadView('reports.Components.expenses-pdf', [
            'expensesList' => $expensesList,
            'totalExpenses' => $totalExpenses,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        return $pdf->download('expenses-report-' . date('Y-m-d') . '.pdf');
        */
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $currency = CompanyInformation::first()?->currency ?? 'Rs.';

        $expensesList = Expense::with(['user:id,name', 'supplier:id,name'])
            ->select('id', 'title', 'amount', 'remark', 'expense_date', 'payment_type', 'user_id', 'supplier_id', 'reference')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->orderBy('expense_date', 'desc')
            ->get();

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('reports.Components.expenses-pdf', [
                'expensesList' => $expensesList,
                'totalExpenses' => $expensesList->sum('amount'),
                'startDate' => $startDate,
                'endDate' => $endDate,
                'currency' => $currency,
            ]);
            return $pdf->download('expenses-report-' . date('Y-m-d') . '.pdf');
        }

        return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
    }

    /**
     * Export expenses report as Excel
     *
     * Generates an Excel file with expense details:
     * Date, Title, Supplier, Amount, Payment Type, Reference, Remark, User
     * Supports filtering by date range and supplier.
     *
     * @param Request $request - Contains start_date, end_date, and optional supplier_id parameters
    * @return \Symfony\Component\HttpFoundation\Response Excel file download
     */
    public function exportExpensesExcel(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $supplierId = $request->input('supplier_id');

        return Excel::download(
            new \App\Exports\ExpensesReportExport($startDate, $endDate, $supplierId),
            'supplierPaymentExpenses-report-' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export income report as PDF
     *
     * Generates a PDF report with income details:
     * - Income by payment type (Cash, Card, Credit)
     * - Total amounts and transaction counts
     *
     * @param Request $request - Contains start_date and end_date parameters
    * @return \Symfony\Component\HttpFoundation\Response PDF download
     */
    public function exportIncomePdf(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $incomeSummary = Income::select(
                'payment_type',
                'transaction_type',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->whereBetween('income_date', [$startDate, $endDate])
            ->groupBy('payment_type', 'transaction_type')
            ->get()
            ->map(function ($item) {
                $paymentTypes = ['Cash', 'Card', 'Credit'];
                return [
                    'payment_type' => $item->payment_type,
                    'payment_type_name' => $paymentTypes[$item->payment_type] ?? 'Unknown',
                    'transaction_type' => $item->transaction_type ?? 'N/A',
                    'total_amount' => $item->total_amount,
                    'transaction_count' => $item->transaction_count,
                ];
            });

        $totalIncome = Income::whereBetween('income_date', [$startDate, $endDate])
            ->sum('amount');

        $currencySymbol = CompanyInformation::first()?->currency ?? 'Rs.';

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('reports.Components.income-pdf', [
                'incomeSummary' => $incomeSummary,
                'totalIncome' => $totalIncome,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'currency' => $currencySymbol,
            ]);
            return $pdf->download('income-report-' . date('Y-m-d') . '.pdf');
        }

        return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
    }

    /**
     * Export income report as Excel/CSV
     *
     * Streams a CSV file with income details:
     * Payment Type, Amount, Transaction Count
     *
     * @param Request $request - Contains start_date and end_date parameters
    * @return \Symfony\Component\HttpFoundation\Response CSV stream download
     */
    public function exportIncomeExcel(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $currency = CompanyInformation::first()?->currency ?? 'Rs.';

        $incomeSummary = Income::select(
                'payment_type',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->whereBetween('income_date', [$startDate, $endDate])
            ->groupBy('payment_type')
            ->get()
            ->map(function ($item) {
                $paymentTypes = ['Cash', 'Card', 'Credit'];
                return [
                    'payment_type' => $paymentTypes[$item->payment_type] ?? 'Unknown',
                    'total_amount' => $item->total_amount,
                    'transaction_count' => $item->transaction_count,
                ];
            });

        return response()->stream(function () use ($incomeSummary, $currency) {
            $handle = fopen('php://output', 'w');

            // CSV header
            fputcsv($handle, ['Payment Type', 'Amount', 'Transaction Count']);

            // CSV rows
            foreach ($incomeSummary as $income) {
                fputcsv($handle, [
                    $income['payment_type'],
                    $currency . ' ' . $income['total_amount'],
                    $income['transaction_count'],
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="income-report-' . date('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Export order history report PDF
     */
    public function exportSalesIncomePdf(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $currency = $request->input('currency', CompanyInformation::first()?->currency ?? 'Rs.');

        $incomeQuery = Income::with('sale')
            ->whereBetween('income_date', [$startDate, $endDate]);
        $this->applyCashierDivisionFilterToIncome($incomeQuery);

        // Fetch all income records with sale information
        $salesIncomeList = (clone $incomeQuery)
            ->orderBy('income_date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($item) {
                $isReturn = in_array($item->transaction_type, ['product_return', 'cash_return']);
                $type = $isReturn ? 'Return' : 'Income';
                return [
                    'id' => $item->id,
                    'invoice_no' => $item->sale?->invoice_no ?? 'N/A',
                    'income_date' => $item->income_date,
                    'amount' => number_format($item->sale?->total_amount ?? 0, 2),
                    'type' => $type,
                    'is_return' => $isReturn,
                    'payment_type' => $item->payment_type,
                    'payment_type_name' => $this->getIncomePaymentTypeName($item->payment_type, $item->card_type),
                    'transaction_type' => $item->transaction_type ?? 'sale',
                ];
            });

        // Calculate totals
        $totalIncome = (clone $incomeQuery)
            ->whereNotIn('transaction_type', ['product_return', 'cash_return'])
            ->sum('amount');

        $totalReturns = (clone $incomeQuery)
            ->whereIn('transaction_type', ['product_return', 'cash_return'])
            ->sum('amount');

        $netIncome = $totalIncome - $totalReturns;

        $data = [
            'salesIncomeList' => $salesIncomeList,
            'totalIncome' => number_format($totalIncome, 2),
            'totalReturns' => number_format($totalReturns, 2),
            'netIncome' => number_format($netIncome, 2),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currency' => $currency,
        ];

        $pdf = Pdf::loadView('pdf.sales-income-report', $data);
        return $pdf->download('sales-income-report-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export order history report as Excel/CSV
     */
    public function exportSalesIncomeExcel(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $currency = $request->input('currency', CompanyInformation::first()?->currency ?? 'Rs.');

        $incomeQuery = Income::with('sale')
            ->whereBetween('income_date', [$startDate, $endDate]);
        $this->applyCashierDivisionFilterToIncome($incomeQuery);

        // Fetch all income records with sale information
        $salesIncomeList = (clone $incomeQuery)
            ->orderBy('income_date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($item) {
                $isReturn = in_array($item->transaction_type, ['product_return', 'cash_return']);
                $type = $isReturn ? 'Return' : 'Income';
                return [
                    'id' => $item->id,
                    'invoice_no' => $item->sale?->invoice_no ?? 'N/A',
                    'income_date' => $item->income_date,
                    'amount' => number_format($item->sale?->total_amount ?? 0, 2),
                    'type' => $type,
                    'is_return' => $isReturn,
                    'payment_type' => $item->payment_type,
                    'payment_type_name' => $this->getIncomePaymentTypeName($item->payment_type, $item->card_type),
                    'transaction_type' => $item->transaction_type ?? 'sale',
                ];
            });

        return response()->stream(function () use ($salesIncomeList, $currency) {
            $handle = fopen('php://output', 'w');

            // CSV header
            fputcsv($handle, ['Invoice No', 'Income Date', 'Amount', 'Type', 'Payment Type']);

            // CSV rows
            foreach ($salesIncomeList as $income) {
                fputcsv($handle, [
                    $income['invoice_no'],
                    $income['income_date'],
                    $currency . ' ' . $income['amount'],
                    $income['type'],
                    $income['payment_type_name'],
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sales-income-report-' . date('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Export product sales (by product) as PDF
     */
    public function exportProductSalesPdf(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $currency = CompanyInformation::first()?->currency ?? 'Rs.';

        $productSalesReport = Product::select('id', 'name', 'barcode')
            ->with([
                'salesProducts' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'quantity', 'price', 'total', 'sale_id')
                        ->whereHas('sale', function($q) use ($startDate, $endDate) {
                            $q->whereBetween('sale_date', [$startDate, $endDate]);
                        });
                },
                'returnProducts' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'quantity', 'price', 'total', 'sales_return_id')
                        ->whereHas('salesReturn', function($q) use ($startDate, $endDate) {
                            $q->whereBetween('return_date', [$startDate, $endDate])
                              ->where('status', 1);
                        });
                }
            ])
            ->get()
            ->map(function ($product) use ($currency) {
                $totalSalesQty = $product->salesProducts->sum('quantity');
                $totalSalesAmount = $product->salesProducts->sum('total');
                $totalReturnsQty = $product->returnProducts->sum('quantity');
                $totalReturnsAmount = $product->returnProducts->sum('total');
                $netSalesQty = $totalSalesQty - $totalReturnsQty;
                $netSalesAmount = $totalSalesAmount - $totalReturnsAmount;
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'sales_quantity' => $totalSalesQty,
                    'sales_amount' => number_format($totalSalesAmount, 2),
                    'returns_quantity' => $totalReturnsQty,
                    'returns_amount' => number_format($totalReturnsAmount, 2),
                    'net_sales_quantity' => $netSalesQty,
                    'net_sales_amount' => number_format($netSalesAmount, 2),
                ];
            })
            ->filter(function ($item) {
                return $item['sales_quantity'] > 0 || $item['returns_quantity'] > 0;
            })
            ->values();

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('reports.Components.product-sales-pdf', [
                'productSalesReport' => $productSalesReport,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'currency' => $currency,
            ]);
            return $pdf->download('product-sales-report-' . date('Y-m-d') . '.pdf');
        }

        return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
    }

    /**
     * Export product sales (by product) as CSV
     */
    public function exportProductSalesExcel(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $currency = CompanyInformation::first()?->currency ?? 'Rs.';

        $productSalesReport = Product::select('id', 'name', 'barcode')
            ->with([
                'salesProducts' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'quantity', 'price', 'total', 'sale_id')
                        ->whereHas('sale', function($q) use ($startDate, $endDate) {
                            $q->whereBetween('sale_date', [$startDate, $endDate]);
                        });
                },
                'returnProducts' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'quantity', 'price', 'total', 'sales_return_id')
                        ->whereHas('salesReturn', function($q) use ($startDate, $endDate) {
                            $q->whereBetween('return_date', [$startDate, $endDate])
                              ->where('status', 1);
                        });
                }
            ])
            ->get()
            ->map(function ($product) use ($currency) {
                $totalSalesQty = $product->salesProducts->sum('quantity');
                $totalSalesAmount = $product->salesProducts->sum('total');
                $totalReturnsQty = $product->returnProducts->sum('quantity');
                $totalReturnsAmount = $product->returnProducts->sum('total');
                $netSalesQty = $totalSalesQty - $totalReturnsQty;
                $netSalesAmount = $totalSalesAmount - $totalReturnsAmount;
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'sales_quantity' => $totalSalesQty,
                    'sales_amount' => $totalSalesAmount,
                    'returns_quantity' => $totalReturnsQty,
                    'returns_amount' => $totalReturnsAmount,
                    'net_sales_quantity' => $netSalesQty,
                    'net_sales_amount' => $netSalesAmount,
                    "currency" => $currency,
                ];
            })
            ->filter(function ($item) {
                return $item['sales_quantity'] > 0 || $item['returns_quantity'] > 0;
            })
            ->values();

        $filename = 'product-sales-report-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['ID','Name','Barcode','Sales Qty','Sales Amount','Returns Qty','Returns Amount','Net Qty','Net Amount'];

        $callback = function() use ($productSalesReport, $columns, $currency) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($productSalesReport as $row) {
                fputcsv($file, [
                    $row['id'],
                    $row['name'],
                    $row['barcode'],
                    $row['sales_quantity'],
                    $currency . ' ' . $row['sales_amount'],
                    $row['returns_quantity'],
                    $currency . ' ' . $row['returns_amount'],
                    $row['net_sales_quantity'],
                    $currency . ' ' . $row['net_sales_amount'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Individual Report Pages

    /**
     * Display detailed sales report
     *
     * Shows sales data with filtering options:
     * - Date range
     * - Sale type (Retail/Wholesale)
     * - Payment type (Cash/Card/Credit)
     * Includes sales returns adjustment and balance tracking.
     *
     * @param Request $request - Contains filter parameters
     * @return \Inertia\Response
     */
    public function salesReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
           $currencySymbol  = CompanyInformation::first();

        // Income summary by payment type
        $incomeSummary = Income::select(
                'payment_type',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->whereBetween('income_date', [$startDate, $endDate])
            ->groupBy('payment_type')
            ->get()
            ->map(function ($item) {
                $paymentTypes = ['Cash', 'Card', 'Credit'];
                return [
                    'payment_type' => $item->payment_type,
                    'payment_type_name' => $paymentTypes[$item->payment_type] ?? 'Unknown',
                    'total_amount' => number_format($item->total_amount, 2),
                    'transaction_count' => $item->transaction_count,
                ];
            });

        $totalIncome = Income::whereBetween('income_date', [$startDate, $endDate])->sum('amount');

        // Sales summary with returns
        $salesSummary = Sale::select(
                'type',
                DB::raw('COUNT(*) as total_sales'),
                DB::raw('SUM(total_amount) as gross_total'),
                DB::raw('SUM(discount) as total_discount'),
                DB::raw('SUM(net_amount) as net_total'),
                DB::raw('SUM(balance) as total_balance')
            )
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->groupBy('type')
            ->get()
            ->map(function ($item) use ($startDate, $endDate) {
                $types = [1 => 'Retail', 2 => 'Wholesale'];

                $totalReturns = DB::table('sales_return')
                    ->join('sales', 'sales_return.sale_id', '=', 'sales.id')
                    ->join('sales_return_products', 'sales_return.id', '=', 'sales_return_products.sales_return_id')
                    ->where('sales.type', $item->type)
                    ->where('sales_return.status', 1)
                    ->whereBetween('sales.sale_date', [$startDate, $endDate])
                    ->sum('sales_return_products.total');

                $netTotalAfterReturns = $item->net_total - $totalReturns;

                return [
                    'type' => $item->type,
                    'type_name' => $types[$item->type] ?? 'Unknown',
                    'total_sales' => $item->total_sales,
                    'gross_total' => number_format($item->gross_total, 2),
                    'total_discount' => number_format($item->total_discount, 2),
                    'net_total' => number_format($item->net_total, 2),
                    'total_returns' => number_format($totalReturns, 2),
                    'net_total_after_returns' => number_format($netTotalAfterReturns, 2),
                    'total_balance' => number_format($item->total_balance, 2),
                ];
            });

        $totalSalesCount = Sale::whereBetween('sale_date', [$startDate, $endDate])->count();

        // Product Sales Report
        $productSalesReport = Product::select('id', 'name', 'barcode')
            ->with([
                'salesProducts.sale' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'sale_date');
                },
                'salesProducts' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'quantity', 'price', 'total', 'sale_id')
                        ->whereHas('sale', function($q) use ($startDate, $endDate) {
                            $q->whereBetween('sale_date', [$startDate, $endDate]);
                        });
                },
                'returnProducts.salesReturn' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'return_date', 'status');
                },
                'returnProducts' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'quantity', 'price', 'total', 'sales_return_id')
                        ->whereHas('salesReturn', function($q) use ($startDate, $endDate) {
                            $q->whereBetween('return_date', [$startDate, $endDate])
                              ->where('status', 1);
                        });
                }
            ])
            ->get()
            ->map(function ($product) {
                $totalSalesQty = $product->salesProducts->sum('quantity');
                $totalSalesAmount = $product->salesProducts->sum('total');
                $totalReturnsQty = $product->returnProducts->sum('quantity');
                $totalReturnsAmount = $product->returnProducts->sum('total');
                $netSalesQty = $totalSalesQty - $totalReturnsQty;
                $netSalesAmount = $totalSalesAmount - $totalReturnsAmount;

                // Get the earliest sale date for this product in the range
                $salesDate = $product->salesProducts->sortBy(function($sp) {
                    return $sp->sale ? $sp->sale->sale_date : null;
                })->first();
                if ($salesDate && $salesDate->sale && $salesDate->sale->sale_date) {
                    $salesDateFormatted = \Carbon\Carbon::parse($salesDate->sale->sale_date)->format('Y-m-d');
                } else {
                    $salesDateFormatted = null;
                }

                $returnsDateObj = $product->returnProducts->sortBy(function($rp) {
                    return $rp->salesReturn ? $rp->salesReturn->return_date : null;
                })->first();
                if ($returnsDateObj && $returnsDateObj->salesReturn && $returnsDateObj->salesReturn->return_date) {
                    $returnsDateFormatted = \Carbon\Carbon::parse($returnsDateObj->salesReturn->return_date)->format('Y-m-d');
                } else {
                    $returnsDateFormatted = null;
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'sales_date' => $salesDateFormatted,
                    'sales_quantity' => $totalSalesQty,
                    'sales_amount' => number_format($totalSalesAmount, 2),
                    'returns_date' => $returnsDateFormatted,
                    'returns_quantity' => $totalReturnsQty,
                    'returns_amount' => number_format($totalReturnsAmount, 2),
                    'net_sales_quantity' => $netSalesQty,
                    'net_sales_amount' => number_format($netSalesAmount, 2),
                ];
            })
            ->filter(function ($item) {
                return $item['sales_quantity'] > 0 || $item['returns_quantity'] > 0;
            })
            ->values();

        return Inertia::render('Reports/SalesReport', [
            'incomeSummary' => $incomeSummary,
            'salesSummary' => $salesSummary,
            'productSalesReport' => $productSalesReport,
            'totalIncome' => number_format($totalIncome, 2),
            'totalSalesCount' => $totalSalesCount,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    /**
     * Display product-wise sales report
     *
     * Analyzes sales performance at product level with metrics:
     * - Total quantity sold
     * - Total revenue
     * - Average price
     * Filterable by date range and sale type.
     *
     * @param Request $request - Contains filter parameters
     * @return \Inertia\Response
     */
    public function productSalesReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $divisionId = $request->input('division_id');
        $currencySymbol  = CompanyInformation::first();

        $productSalesReport = Product::select('id', 'name', 'barcode', 'division_id')
            ->when($divisionId, fn($q) => $q->where('division_id', $divisionId))
            ->with([
                'salesProducts' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'quantity', 'price', 'total', 'sale_id')
                        ->whereHas('sale', function($q) use ($startDate, $endDate) {
                            $q->whereBetween('sale_date', [$startDate, $endDate]);
                        });
                },
                'returnProducts' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'quantity', 'price', 'total', 'sales_return_id')
                        ->whereHas('salesReturn', function($q) use ($startDate, $endDate) {
                            $q->whereBetween('return_date', [$startDate, $endDate])
                              ->where('status', 1);
                        });
                }
            ])
            ->get()
            ->map(function ($product) {
                $totalSalesQty = $product->salesProducts->sum('quantity');
                $totalSalesAmount = $product->salesProducts->sum('total');
                $totalReturnsQty = $product->returnProducts->sum('quantity');
                $totalReturnsAmount = $product->returnProducts->sum('total');
                $netSalesQty = $totalSalesQty - $totalReturnsQty;
                $netSalesAmount = $totalSalesAmount - $totalReturnsAmount;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'sales_quantity' => $totalSalesQty,
                    'sales_amount' => number_format($totalSalesAmount, 2),
                    'returns_quantity' => $totalReturnsQty,
                    'returns_amount' => number_format($totalReturnsAmount, 2),
                    'net_sales_quantity' => $netSalesQty,
                    'net_sales_amount' => number_format($netSalesAmount, 2),
                ];
            })
            ->filter(function ($item) {
                return $item['sales_quantity'] > 0 || $item['returns_quantity'] > 0;
            })
            ->values();

        $divisions = \App\Models\Division::active()->get(['id', 'name', 'slug']);

        return Inertia::render('Reports/ProductSalesReport', [
            'productSalesReport' => $productSalesReport,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currencySymbol' => $currencySymbol,
            'divisions' => $divisions,
            'divisionId' => $divisionId,
        ]);
    }

    /**
     * Product Movement Based Sales Optimization Report
     *
     * Analyzes product movement and sales data to suggest sales optimization strategies:
     * - Fast-moving vs slow-moving products
     * - Products with high stock but low sales
     * - Seasonal trends and recommendations
     * - Revenue optimization opportunities
     *
     * @param Request $request - Contains filter parameters
     * @return \Inertia\Response
     */
    public function productMovementSalesOptimizationReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $currencySymbol = CompanyInformation::first();
        $currency = $currencySymbol?->currency ?? 'Rs.';

        // Get products with sales and movement data
        $products = Product::select('id', 'name', 'barcode', 'shop_quantity_in_sales_unit', 'store_quantity_in_purchase_unit', 'retail_price', 'wholesale_price', 'sales_unit_id')
            ->with([
                'salesProducts' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'quantity', 'price', 'total', 'sale_id')
                        ->whereHas('sale', function($q) use ($startDate, $endDate) {
                            $q->whereBetween('sale_date', [$startDate, $endDate]);
                        });
                },
                'productMovements' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'movement_type', 'quantity', 'created_at')
                        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                },
                // Select id, name, and symbol for the sales unit (if symbol exists)
                'salesUnit:id,name,symbol'
            ])
            ->get()
            ->map(function ($product) use ($startDate, $endDate) {
                $totalSalesQty = $product->salesProducts->sum('quantity');
                $totalSalesAmount = $product->salesProducts->sum('total');
                $totalStock = $product->shop_quantity_in_sales_unit ;

                $daysDiff = max(1, \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)));
                $salesVelocity = $totalSalesQty / $daysDiff;
                $classification = 'Unknown';
                if ($salesVelocity >= 5) {
                    $classification = 'Fast Moving';
                } elseif ($salesVelocity >= 1) {
                    $classification = 'Medium Moving';
                } elseif ($salesVelocity > 0) {
                    $classification = 'Slow Moving';
                } else {
                    $classification = 'No Sales';
                }

                // Optimization recommendation (optional, can be customized)
                $recommendation = '';
                if ($classification === 'No Sales' && $totalStock > 0) {
                    $recommendation = 'No Sales - Review pricing or consider discontinuing';
                } elseif ($classification === 'Fast Moving') {
                    $recommendation = 'Fast Moving - Increase stock levels if needed';
                } else {
                    $recommendation = 'Optimal performance';
                }

                $salesUnitSymbol = $product->salesUnit && isset($product->salesUnit->symbol) && $product->salesUnit->symbol
                    ? $product->salesUnit->symbol
                    : ($product->salesUnit ? $product->salesUnit->name : '');

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'current_stock' => $totalStock,
                    'sales_quantity' => $totalSalesQty,
                    'sales_amount' => round((float) $totalSalesAmount, 2),
                    'classification' => $classification,
                    'recommendation' => $recommendation,
                    'sales_unit_symbol' => $salesUnitSymbol,
                ];
            })
            ->sortByDesc('sales_velocity')
            ->values();

        // Summary statistics (only Fast Moving and No Sales)
        $summary = [
            'total_products' => $products->count(),
            'fast_moving' => $products->where('classification', 'Fast Moving')->count(),
            'no_sales' => $products->where('classification', 'No Sales')->count(),
            'total_revenue' => round($products->sum(function($p) { return (float) $p['sales_amount']; }), 2),
        ];

        return Inertia::render('Reports/ProductMovementSalesOptimization', [
            'products' => $products,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currencySymbol' => $currencySymbol,
            'currency' => $currency,
        ]);
    }

    /**
     * Export Product Movement Sales Optimization report as PDF
     *
     * @param Request $request
    * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportProductMovementSalesOptimizationPdf(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $classificationFilter = $request->input('classification', null);

        // Rebuild the products collection (same logic as the page)
        $products = Product::select('id', 'name', 'barcode', 'shop_quantity_in_sales_unit', 'store_quantity_in_purchase_unit', 'retail_price', 'wholesale_price')
            ->with([
                'salesProducts' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'quantity', 'price', 'total', 'sale_id')
                        ->whereHas('sale', function($q) use ($startDate, $endDate) {
                            $q->whereBetween('sale_date', [$startDate, $endDate]);
                        });
                },
                'productMovements' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'movement_type', 'quantity', 'created_at')
                        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                }
            ])
            ->get()
            ->map(function ($product) {
                $totalSalesQty = $product->salesProducts->sum('quantity');
                $totalSalesAmount = $product->salesProducts->sum('total');
                $totalStock = $product->shop_quantity_in_sales_unit + $product->store_quantity_in_purchase_unit;

                $daysDiff = max(1, Carbon::parse(request()->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')))
                    ->diffInDays(Carbon::parse(request()->input('end_date', Carbon::now()->format('Y-m-d')))));
                $salesVelocity = $totalSalesQty / $daysDiff;
                $stockTurnoverDays = $salesVelocity > 0 ? $totalStock / $salesVelocity : 999;
                $revenuePerUnit = $totalSalesQty > 0 ? $totalSalesAmount / $totalSalesQty : 0;

                $classification = 'Unknown';
                if ($salesVelocity >= 5) {
                    $classification = 'Fast Moving';
                } elseif ($salesVelocity >= 1) {
                    $classification = 'Medium Moving';
                } elseif ($salesVelocity > 0) {
                    $classification = 'Slow Moving';
                } else {
                    $classification = 'No Sales';
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'current_stock' => $totalStock,
                    'sales_quantity' => $totalSalesQty,
                    'sales_amount' => round((float) $totalSalesAmount, 2),
                    'sales_velocity' => round((float) $salesVelocity, 2),
                    'stock_turnover_days' => round((float) $stockTurnoverDays, 1),
                    'revenue_per_unit' => round((float) $revenuePerUnit, 2),
                    'retail_price' => round((float) $product->retail_price, 2),
                    'classification' => $classification,
                ];
            })
            ->sortByDesc('sales_velocity')
            ->values();

        if ($classificationFilter) {
            $products = $products->filter(function ($p) use ($classificationFilter) {
                return $p['classification'] === $classificationFilter;
            })->values();
        }

        $currencySymbol = CompanyInformation::first();
        $currency = $currencySymbol?->currency ?? 'Rs.';

        // Generate PDF using DomPDF if available
        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('reports.Components.product-movement-sales-optimization-pdf', [
                'products' => $products,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'currency' => $currency,
                'classification' => $classificationFilter,
            ]);
            return $pdf->download('product-movement-sales-optimization-' . date('Y-m-d') . '.pdf');
        }

        return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
    }

    /**
     * Export Product Movement Sales Optimization report as CSV
     *
     * @param Request $request
    * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportProductMovementSalesOptimizationCsv(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $classificationFilter = $request->input('classification', null);

        // Rebuild products same as page
        $products = Product::select('id', 'name', 'barcode', 'shop_quantity_in_sales_unit', 'store_quantity_in_purchase_unit', 'retail_price', 'wholesale_price')
            ->with([
                'salesProducts' => function($query) use ($startDate, $endDate) {
                    $query->select('id', 'product_id', 'quantity', 'price', 'total', 'sale_id')
                        ->whereHas('sale', function($q) use ($startDate, $endDate) {
                            $q->whereBetween('sale_date', [$startDate, $endDate]);
                        });
                }
            ])
            ->get()
            ->map(function ($product) {
                $totalSalesQty = $product->salesProducts->sum('quantity');
                $totalSalesAmount = $product->salesProducts->sum('total');
                $totalStock = $product->shop_quantity_in_sales_unit + $product->store_quantity_in_purchase_unit;

                $daysDiff = max(1, Carbon::parse(request()->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')))
                    ->diffInDays(Carbon::parse(request()->input('end_date', Carbon::now()->format('Y-m-d')))));
                $salesVelocity = $totalSalesQty / $daysDiff;

                $classification = 'Unknown';
                if ($salesVelocity >= 5) {
                    $classification = 'Fast Moving';
                } elseif ($salesVelocity >= 1) {
                    $classification = 'Medium Moving';
                } elseif ($salesVelocity > 0) {
                    $classification = 'Slow Moving';
                } else {
                    $classification = 'No Sales';
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'current_stock' => $totalStock,
                    'sales_quantity' => $totalSalesQty,
                    'sales_amount' => round((float) $totalSalesAmount, 2),
                    'classification' => $classification,
                ];
            })
            ->sortByDesc('sales_velocity')
            ->values();

        if ($classificationFilter) {
            $products = $products->filter(function ($p) use ($classificationFilter) {
                return $p['classification'] === $classificationFilter;
            })->values();
        }

        $filename = 'product-movement-sales-optimization-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['ID','Product','Barcode','Current Stock','Sales Quantity','Sales Amount','Classification'];

        $callback = function() use ($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($products as $p) {
                fputcsv($file, [
                    $p['id'],
                    $p['name'],
                    $p['barcode'],
                    $p['current_stock'],
                    $p['sales_quantity'],
                    $p['sales_amount'],
                    $p['classification'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display current stock levels report
     *
     * Shows inventory status for all products including:
     * - Current quantity on hand
     * - Retail and wholesale prices
     * - Stock valuation
     *
     * @return \Inertia\Response
     */
    public function stockReport()
    {
        $productQuery = Product::with(['salesUnit', 'purchaseUnit'])
            ->select(
                'id',
                'name',
                'shop_quantity_in_sales_unit',
                'store_quantity_in_purchase_unit',
                'store_quantity_in_transfer_unit',
                'store_quantity_in_sale_unit',
                'sales_unit_id',
                'purchase_unit_id',
                'transfer_unit_id'
            );

        $this->applyCashierDivisionFilterToProducts($productQuery);

        $productsStock = $productQuery
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        // Transform the paginated collection
        $productsStock->getCollection()->transform(function ($item) {
            $shopUnit = $item->salesUnit ? $item->salesUnit->name : '';
            $storeUnit = $item->purchaseUnit ? $item->purchaseUnit->name : '';
            $transferUnit = $item->transferUnit ? $item->transferUnit->name : '';
             $rawTransferQty = $item->getAttributes()['store_quantity_in_transfer_unit'] ?? 0;
             $rawSalesQty = $item->getAttributes()['store_quantity_in_sale_unit'] ?? 0;

            return [
                'id' => $item->id,
                'name' => $item->name,
                'shop_quantity' => $item->shop_quantity_in_sales_unit,
                'store_quantity' => $item->store_quantity_in_purchase_unit,
                'transfer_quantity' => $item->store_quantity_in_transfer_unit, // calculated
                'raw_transfer_quantity' => $rawTransferQty, // raw DB value
                'loose_bundles' => $rawTransferQty . ' ' . $transferUnit,
                'loose_bottles' =>$rawSalesQty. ' ' . $shopUnit,
                'shop_qty_display' => $item->shop_quantity_in_sales_unit . ' ' . $shopUnit,
                'store_qty_display' => $item->store_quantity_in_purchase_unit . ' ' . $storeUnit,
                'purchase_unit' => $item->purchaseUnit,
                'transfer_unit' => $item->transferUnit,
                'sales_unit' => $item->salesUnit,
            ];
        });

        $currencySymbol = CompanyInformation::first();
        $currency = "LKR";

        return Inertia::render('Reports/StockReport', [
            'productsStock' => $productsStock,
            'currencySymbol' => $currencySymbol,
            'currency' => $currency,
        ]);
    }

    /**
     * Display expenses report
     *
     * Lists all expenses with category breakdown for the specified
     * date range. Includes total expenses calculation.
     *
     * @param Request $request - Contains start_date and end_date parameters
     * @return \Inertia\Response
     */
    public function expensesReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $supplierId = $request->input('supplier_id');

        $expensesSummary = Expense::select(
                'payment_type',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->when($supplierId, function($query) use ($supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->groupBy('payment_type')
            ->get()
            ->map(function ($item) {
                $paymentTypes = [0 => 'Cash', 1 => 'Card', 2 => 'Cheque'];
                return [
                    'payment_type' => $item->payment_type,
                    'payment_type_name' => $paymentTypes[$item->payment_type] ?? 'Unknown',
                    'total_amount' => number_format($item->total_amount, 2),
                    'transaction_count' => $item->transaction_count,
                ];
            });

        $totalExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->when($supplierId, function($query) use ($supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->sum('amount');

        $expensesList = Expense::with(['user:id,name', 'supplier:id,name'])
            ->select('id', 'title', 'amount', 'remark', 'expense_date', 'payment_type', 'card_type', 'user_id', 'supplier_id', 'reference')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->when($supplierId, function($query) use ($supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->orderBy('expense_date', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Transform the paginated collection
        $expensesList->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'remark' => $item->remark,
                'amount' => number_format($item->amount, 2),
                'expense_date' => $item->expense_date,
                'payment_type' => $item->payment_type,
                'payment_type_name' => $this->getExpensePaymentTypeName($item->payment_type, $item->card_type),
                'reference' => $item->reference,
                'user_name' => $item->user->name ?? 'N/A',
                'supplier_name' => $item->supplier->name ?? 'N/A',
            ];
        });

        // Get all suppliers for the dropdown
        $suppliers = Supplier::select('id', 'name')
            ->orderBy('name')
            ->get();

        $currencySymbol = CompanyInformation::first();

        return Inertia::render('Reports/ExpensesReport', [
            'expensesSummary' => $expensesSummary,
            'expensesList' => $expensesList,
            'totalExpenses' => number_format($totalExpenses, 2),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'suppliers' => $suppliers,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    /**
     * Backwards-compatible alias for `expensesReport`.
     * Some routes or callers use the singular `expenseReport` name;
     * delegate to the plural implementation to avoid undefined method errors.
     */
    public function expenseReport(Request $request)
    {
        return $this->expensesReport($request);
    }

    /**
     * Display income report
     *
     * Shows all income transactions with breakdown by payment type:
     * - Cash payments
     * - Card payments
     * - Credit transactions
     *
     * @param Request $request - Contains start_date and end_date parameters
     * @return \Inertia\Response
     */
    public function incomeReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // Calculate income summary by payment type, subtracting cash_return amounts
        $incomeSummary = Income::select(
                'payment_type',
                DB::raw("SUM(CASE WHEN transaction_type = 'cash_return' THEN -amount ELSE amount END) as total_amount"),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->whereBetween('income_date', [$startDate, $endDate])
            ->groupBy('payment_type')
            ->get()
            ->map(function ($item) {
                $paymentTypes = ['Cash', 'Card', 'Credit'];
                return [
                    'payment_type' => $item->payment_type,
                    'payment_type_name' => $paymentTypes[$item->payment_type] ?? 'Unknown',
                    'total_amount' => number_format($item->total_amount, 2),
                    'transaction_count' => $item->transaction_count,
                ];
            });

        // Calculate total income, subtracting cash_return amounts
        $totalIncome = Income::whereBetween('income_date', [$startDate, $endDate])
            ->selectRaw("SUM(CASE WHEN transaction_type = 'cash_return' THEN -amount ELSE amount END) as total")
            ->value('total') ?? 0;

        // Build detailed income list for the view
        $incomeList = Income::whereBetween('income_date', [$startDate, $endDate])
            ->orderBy('income_date', 'desc')
            ->get()
            ->map(function ($item) {
                $paymentTypes = ['Cash', 'Card', 'Credit'];
                return [
                    'id' => $item->id,
                    'sale_id' => $item->sale_id,
                    'source' => $item->source,
                    'income_date' => $item->income_date,
                    'payment_type' => $item->payment_type,
                    'payment_type_name' => $paymentTypes[$item->payment_type] ?? 'Unknown',
                    'transaction_type' => $item->transaction_type ?? 'N/A',
                    'amount' => number_format($item->amount, 2),
                    'remark' => $item->remark,
                ];
            });

        $currencySymbol = CompanyInformation::first();
        $currency = $currencySymbol?->currency ?? 'Rs.';

        return Inertia::render('Reports/IncomeReport', [
            'incomeSummary' => $incomeSummary,
            'incomeList' => $incomeList,
            'totalIncome' => number_format($totalIncome, 2),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currencySymbol' => $currencySymbol,
            'currency' => $currency,
        ]);
    }

    /**
     * Display Order History Report
     *
     * Shows all sales income and return transactions from the income table
     * with invoice numbers, amounts (colored by type), payment types, etc.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function salesIncomeReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $incomeQuery = Income::with('sale')
            ->whereBetween('income_date', [$startDate, $endDate]);
        $this->applyCashierDivisionFilterToIncome($incomeQuery);

        // Fetch paginated income records with sale information
        $salesIncomeList = (clone $incomeQuery)
            ->orderBy('income_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Transform the paginated collection
        $salesIncomeList->getCollection()->transform(function ($item) {
            $isReturn = in_array($item->transaction_type, ['product_return', 'cash_return']);
            $type = $isReturn ? 'Return' : 'Income';

            return [
                'id' => $item->id,
                'invoice_no' => $item->sale?->invoice_no ?? 'N/A',
                'income_date' => $item->income_date,
                'amount' => number_format($item->amount ?? 0, 2),
                'type' => $type,
                'is_return' => $isReturn,
                'payment_type' => $item->payment_type,
                'payment_type_name' => $this->getIncomePaymentTypeName($item->payment_type, $item->card_type),
                'transaction_type' => $item->transaction_type ?? 'sale',
            ];
        });

        // Calculate totals from all records (not just paginated)
        $totalIncome = (clone $incomeQuery)
            ->whereNotIn('transaction_type', ['product_return', 'cash_return'])
            ->sum('amount');

        $totalReturns = (clone $incomeQuery)
            ->whereIn('transaction_type', ['product_return', 'cash_return'])
            ->sum('amount');

        $netIncome = $totalIncome - $totalReturns;

        $paymentMethodTotals = $this->buildSalesIncomePaymentTotals($startDate, $endDate);

        $currencySymbol = CompanyInformation::first();
        $currency = $currencySymbol?->currency ?? 'Rs.';

        return Inertia::render('Reports/SalesIncomeReport', [
            'salesIncomeList' => $salesIncomeList,
            'totalIncome' => number_format($totalIncome, 2),
            'totalReturns' => number_format($totalReturns, 2),
            'netIncome' => number_format($netIncome, 2),
            'paymentMethodTotals' => $paymentMethodTotals,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currencySymbol' => $currencySymbol,
            'currency' => $currency,
        ]);
    }

    public function salesIncomeTotals(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $paymentMethodTotals = $this->buildSalesIncomePaymentTotals($startDate, $endDate);

        return response()->json([
            'paymentMethodTotals' => $paymentMethodTotals,
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    private function buildSalesIncomePaymentTotals(string $startDate, string $endDate)
    {
        $paymentTypeLabels = [
            0 => 'Cash',
            1 => 'Card',
            2 => 'Credit',
        ];

        $incomeQuery = Income::select('payment_type', DB::raw('SUM(amount) as total_amount'))
            ->whereBetween('income_date', [$startDate, $endDate]);
        $this->applyCashierDivisionFilterToIncome($incomeQuery);

        $paymentTypeSums = $incomeQuery
            ->whereNotIn('transaction_type', ['product_return', 'cash_return'])
            ->groupBy('payment_type')
            ->pluck('total_amount', 'payment_type');

        return collect($paymentTypeLabels)
            ->map(function ($label, $paymentType) use ($paymentTypeSums) {
                $total = (float) ($paymentTypeSums[$paymentType] ?? 0);

                return [
                    'payment_type' => (int) $paymentType,
                    'label' => $label,
                    'total' => $total,
                    'formatted_total' => number_format($total, 2),
                ];
            })
            ->values()
            ->all();
    }

    private function getIncomePaymentTypeName($paymentType, $cardType = null): string
    {
        $paymentType = (int) $paymentType;

        if ($paymentType === 1) {
            $resolvedCardType = strtolower((string) $cardType);

            if ($resolvedCardType === 'visa') {
                return 'Visa';
            }

            if ($resolvedCardType === 'mastercard') {
                return 'MasterCard';
            }

            return 'Card';
        }

        $paymentTypes = [
            0 => 'Cash',
            2 => 'Credit',
        ];

        return $paymentTypes[$paymentType] ?? 'Unknown';
    }

    private function getExpensePaymentTypeName($paymentType, $cardType = null): string
    {
        $paymentType = (int) $paymentType;

        if ($paymentType === 1) {
            $resolvedCardType = strtolower((string) $cardType);

            if ($resolvedCardType === 'visa') {
                return 'Card (Visa)';
            }

            if ($resolvedCardType === 'mastercard') {
                return 'Card (MasterCard)';
            }

            return 'Card';
        }

        $paymentTypes = [
            0 => 'Cash',
            2 => 'Cheque',
            3 => 'Bank Transfer',
        ];

        return $paymentTypes[$paymentType] ?? 'Unknown';
    }

    /**
     * Display Goods Received Note (GRN) report
     *
     * Shows all GRN records with:
     * - Supplier information
     * - Product details and quantities
     * - Pricing and totals (subtotal, discount, tax)
     * Filterable by date range (single day or range).
     *
     * @param Request $request - Contains start_date and end_date parameters
     * @return \Inertia\Response
     */
    public function grnReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $data = $this->buildGoodsReceivedNoteData($startDate, $endDate);
        $currencySymbol = CompanyInformation::first();
        $currency = CompanyInformation::first()?->currency ?? 'Rs.';

        return Inertia::render('Reports/GoodReceivedNoteReport', [
            'grnRows' => $data['rows'],
            'grnTotals' => $data['totals'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currencySymbol' => $currencySymbol,
            'currency' => $currency,
        ]);
    }

    /**
     * Display GRN Return report
     *
     * Shows all goods returned to suppliers with:
     * - Return reference and date
     * - Product breakdown with quantities
     * - Estimated return value
     * Filterable by date range.
     *
     * @param Request $request - Contains start_date and end_date parameters
     * @return \Inertia\Response
     */
    public function grnReturnReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $data = $this->buildGrnReturnData($startDate, $endDate);
        $currencySymbol = CompanyInformation::first();
        $currency = $currencySymbol?->currency ?? 'Rs.';

        return Inertia::render('Reports/GoodsReceivedNoteReturnReport', [
            'returnRows' => $data['rows'],
            'returnTotals' => $data['totals'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currencySymbol' => $currencySymbol,
            'currency' => $currency,
        ]);
    }

    /**
     * Export Goods Received Note Report as PDF
     *
     * Generates a formatted PDF document of all Goods Received Note records
     * including product details, pricing, and summary totals.
     * Uses the view: reports.Components.good-receive-note-pdf.blade.php
     *
     * @param Request $request - Contains start_date and end_date parameters
    * @return \Symfony\Component\HttpFoundation\Response PDF download or error redirect
     */
    public function exportGoodReceiveNotePdf(Request $request)
    {
        try {
            // Extract date range parameters
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
            $data = $this->buildGoodsReceivedNoteData($startDate, $endDate);
            $currency = CompanyInformation::first()?->currency ?? 'Rs.';
            if (class_exists(Pdf::class)) {
                $pdf = Pdf::loadView('reports.Components.good-receive-note-pdf', [
                    'rows' => $data['rows'],
                    'totals' => $data['totals'],
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'currency' => $currency,
                ]);
                return $pdf->download('goods-received-note-report-' . date('Y-m-d') . '.pdf');
            }

            return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
        } catch (\Exception $e) {
            Log::error('GRN PDF Export Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export Goods Received Note Report as Excel/CSV
     *
     * Streams a CSV file with GRN details:
     * Date, GRN No, Supplier, Total Quantity, Subtotal, Discount, Tax, Grand Total
     *
     * @param Request $request - Contains start_date and end_date parameters
    * @return \Symfony\Component\HttpFoundation\Response CSV stream download
     */
    public function exportGoodReceiveNoteExcel(Request $request)
    {
        // Extract date range parameters
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $data = $this->buildGoodsReceivedNoteData($startDate, $endDate);
        $currency = CompanyInformation::first()?->currency ?? 'Rs.';

        $filename = 'goods-received-note-report-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['GRN No','Supplier','Date','Products','Gross','Discount','Tax','Net','Status'];

        $callback = function() use ($data, $columns, $currency) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($data['rows'] as $row) {
                $products = collect($row['items'] ?? [])->map(function ($item) {
                    return ($item['name'] ?? 'N/A') . ' - ' . ($item['quantity'] ?? 0);
                })->implode("\n");

                fputcsv($file, [
                    $row['grn_no'],
                    $row['supplier_name'],
                    $row['date'],
                    $products,
                    $currency . ' ' . $row['gross_total'],
                    $currency . ' ' . ($row['line_discount'] + $row['header_discount']),
                    $currency . ' ' . $row['tax_total'],
                    $currency . ' ' . $row['net_total'],
                    $row['status'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export GRN Return report as PDF
     *
     * Generates a formatted PDF of goods returned to suppliers
     * with item details and estimated value calculations.
     * Uses the view: reports.Components.grn-return-pdf.blade.php
     *
     * @param Request $request - Contains start_date and end_date parameters
    * @return \Symfony\Component\HttpFoundation\Response PDF download or error redirect
     */
    public function exportGoodReceiveNoteReturnPdf(Request $request)
    {
        try {
            // Extract date range parameters
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
            $data = $this->buildGrnReturnData($startDate, $endDate);
            $currency = CompanyInformation::first()?->currency ?? 'Rs.';

            if (class_exists(Pdf::class)) {
                $pdf = Pdf::loadView('reports.Components.good-receive-note-return-pdf', [
                    'rows' => $data['rows'],
                    'totals' => $data['totals'],
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'currency' => $currency,
                ]);
                return $pdf->download('goods-received-note-return-report-' . date('Y-m-d') . '.pdf');
            }

            return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
        } catch (\Exception $e) {
            Log::error('GRN Return PDF Export Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export GRN Return report as Excel/CSV
     *
     * Streams a CSV file with return details:
     * Date, GRN No, Handled By, Total Quantity, Estimated Value
     *
     * @param Request $request - Contains start_date and end_date parameters
    * @return \Symfony\Component\HttpFoundation\Response CSV stream download
     */
    public function exportGoodReceiveNoteReturnExcel(Request $request)
    {
        // Extract date range parameters
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $data = $this->buildGrnReturnData($startDate, $endDate);
        $currency = CompanyInformation::first()?->currency ?? 'Rs.';

        $filename = 'goods-received-notes-return-report-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Date','GRN No','Handled By','Qty','Estimated Value','Items'];

        $callback = function() use ($data, $columns, $currency) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($data['rows'] as $row) {
                $items = collect($row['items'] ?? [])->map(function ($item) {
                    return ($item['product_name'] ?? 'N/A') . ' - ' . ($item['quantity'] ?? 0) . ' pcs';
                })->implode("\n");

                fputcsv($file, [
                    $row['date'],
                    $row['grn_no'],
                    $row['handled_by'],
                    $row['total_quantity'],
                    $currency . ' ' . $row['estimated_value'],
                    $items,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export Product Movement report as PDF
     *
     * Generates a detailed PDF of inventory movements showing:
     * - Movement type (purchase, sale, transfer, return)
     * - Product details and quantities
     * - Summary by movement type
     * - Inbound/Outbound totals
     * Uses the view: reports.Components.product-movement-pdf.blade.php
     *
     * @param Request $request - Contains start_date, end_date, and optional product_id
    * @return \Symfony\Component\HttpFoundation\Response PDF download or error redirect
     */
    public function exportProductMovementPdf(Request $request)
    {
        try {
            // Extract filter parameters
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
            $productId = $request->input('product_id', null);
            $data = $this->buildProductMovementData($startDate, $endDate, $productId);

            if (class_exists(Pdf::class)) {
                $pdf = Pdf::loadView('reports.Components.product-movement-pdf', [
                    'movements' => $data['movements'],
                    'summaryByType' => $data['summaryByType'],
                    'totals' => $data['totals'],
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'productName' => $data['productName'],
                ]);
                return $pdf->download('product-movement-report-' . date('Y-m-d') . '.pdf');
            }

            return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
        } catch (\Exception $e) {
            Log::error('Product Movement PDF Export Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export Product Movement report as Excel/CSV
     *
     * Streams a CSV file with movement details:
     * Date, Product, Product Code, Movement Type, Quantity, Reference
     *
     * @param Request $request - Contains start_date, end_date, and optional product_id
    * @return \Symfony\Component\HttpFoundation\Response CSV stream download
     */
    public function exportProductMovementExcel(Request $request)
    {
        // Extract filter parameters
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $productId = $request->input('product_id', null);
        $data = $this->buildProductMovementData($startDate, $endDate, $productId);

        $filename = 'product-movement-report-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Date','Product','Product Code','Movement Type','Quantity','Reference'];

        $callback = function() use ($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($data['movements'] as $row) {
                fputcsv($file, [
                    $row['date_only'],
                    $row['product_name'],
                    $row['product_code'],
                    $row['movement_type'],
                    $row['quantity'],
                    $row['reference'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display Product Movement report
     *
     * Tracks all inventory movements with detailed breakdown:
     * - Movement types: Purchase, Sale, Transfer, Returns
     * - Product-wise filtering
     * - Summary statistics by movement type
     * - Inbound vs Outbound analysis
     *
     * @param Request $request - Contains start_date, end_date, and optional product_id
     * @return \Inertia\Response
     */
    public function productMovementReport(Request $request)
    {
        // Extract filter parameters
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $productId = $request->input('product_id', null);
        $movementType = $request->input('movement_type', null);

        $movementTypes = [
            ProductMovement::TYPE_PURCHASE => 'Purchase (GRN)',
            ProductMovement::TYPE_PURCHASE_RETURN => 'Purchase Return (PRN)',
            ProductMovement::TYPE_TRANSFER => 'Transfer (PTR)',
            ProductMovement::TYPE_SALE => 'Sale',
            ProductMovement::TYPE_SALE_RETURN => 'Sale Return',
            ProductMovement::TYPE_GRN_RETURN => 'GRN Return',
            ProductMovement::TYPE_STOCK_TRANSFER_RETURN => 'Stock Transfer Return',
        ];

        $query = ProductMovement::with(['product.purchaseUnit', 'product.salesUnit'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($movementType !== null && $movementType !== '') {
            $query->where('movement_type', $movementType);
        }

        $movements = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        $movements->getCollection()->transform(function ($movement) use ($movementTypes) {
            $product = $movement->product;
            $unit = 'Units'; // Default

            // Determine unit based on movement type
            if (in_array($movement->movement_type, [ProductMovement::TYPE_PURCHASE, ProductMovement::TYPE_PURCHASE_RETURN, ProductMovement::TYPE_GRN_RETURN])) {
                $unit = $product->purchaseUnit->name ?? 'Units';
            } elseif (in_array($movement->movement_type, [ProductMovement::TYPE_SALE, ProductMovement::TYPE_SALE_RETURN])) {
                $unit = $product->salesUnit->name ?? 'Units';
            } elseif (in_array($movement->movement_type, [ProductMovement::TYPE_TRANSFER, ProductMovement::TYPE_STOCK_TRANSFER_RETURN])) {
                $unit = $product->transferUnit->name ?? 'Units';
            }

            return (object)[
                'id' => $movement->id,
                'product_name' => $product->name ?? 'N/A',
                'product_code' => $product->barcode ?? 'N/A',
                'movement_type' => $movementTypes[$movement->movement_type] ?? 'Unknown',
                'movement_type_id' => $movement->movement_type,
                'quantity' => round($movement->quantity, 2),
                'unit' => $unit,
                'reference' => $movement->reference ?? '—',
                'date' => $movement->created_at->format('Y-m-d H:i:s'),
                'date_only' => $movement->created_at->format('Y-m-d'),
            ];
        });

        // Summary by movement type (using all data, not paginated)
        $allMovements = ProductMovement::with(['product.purchaseUnit', 'product.salesUnit'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($productId) {
            $allMovements->where('product_id', $productId);
        }

        if ($movementType !== null && $movementType !== '') {
            $allMovements->where('movement_type', $movementType);
        }

        $allMovementsData = $allMovements->get();

        $summaryByType = [];
        foreach ($movementTypes as $typeId => $typeName) {
            $typeTotal = $allMovementsData->where('movement_type', $typeId)->sum('quantity');
            if ($typeTotal != 0) {
                $summaryByType[] = [
                    'type' => $typeName,
                    'quantity' => round($typeTotal, 2),
                    'count' => $allMovementsData->where('movement_type', $typeId)->count(),
                ];
            }
        }

        // Summary by product
        $summaryByProduct = $allMovementsData->groupBy('product_id')
            ->map(function ($items) {
                $product = $items->first()->product ?? null;
                return [
                    'product_id' => $product ? $product->id : null,
                    'product_name' => $product ? $product->name : 'N/A',
                    'product_code' => $product ? $product->barcode : 'N/A',
                    'inbound' => round(
                        $items->whereIn('movement_type', [
                            ProductMovement::TYPE_PURCHASE,
                            ProductMovement::TYPE_SALE_RETURN,
                            ProductMovement::TYPE_GRN_RETURN,
                        ])->sum('quantity'),
                        2
                    ),
                    'outbound' => round(
                        $items->whereIn('movement_type', [
                            ProductMovement::TYPE_SALE,
                            ProductMovement::TYPE_TRANSFER,
                            ProductMovement::TYPE_PURCHASE_RETURN,
                            ProductMovement::TYPE_STOCK_TRANSFER_RETURN,
                        ])->sum('quantity'),
                        2
                    ),
                    'net' => round($items->sum('quantity'), 2),
                ];
            })
            ->values();

        $totals = [
            'total_movements' => $allMovementsData->count(),
            'total_quantity_in' => round(
                $allMovementsData->whereIn('movement_type', [
                    ProductMovement::TYPE_PURCHASE,
                    ProductMovement::TYPE_SALE_RETURN,
                    ProductMovement::TYPE_GRN_RETURN,
                ])->sum('quantity'),
                2
            ),
            'total_quantity_out' => round(
                $allMovementsData->whereIn('movement_type', [
                    ProductMovement::TYPE_SALE,
                    ProductMovement::TYPE_TRANSFER,
                    ProductMovement::TYPE_PURCHASE_RETURN,
                    ProductMovement::TYPE_STOCK_TRANSFER_RETURN,
                ])->sum('quantity'),
                2
            ),
            'unique_products' => $summaryByProduct->count(),
        ];

        $products = Product::where('status', '!=', 0)->orderBy('name')->get();

        $currencySymbol = CompanyInformation::first();
        $currency = $currencySymbol?->currency ?? 'Rs.';

        return Inertia::render('Reports/ProductMovementReport', [
            'movements' => $movements,
            'summaryByType' => $summaryByType,
            'summaryByProduct' => $summaryByProduct,
            'totals' => $totals,
            'products' => $products,
            'selectedProductId' => $productId,
            'selectedMovementType' => $movementType,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currencySymbol' => $currencySymbol,
            'currency' => $currency,
        ]);
    }
    /**
     * Products Low Stock (Store & Shop)
     */
    public function lowStockReport(Request $request)
    {
        // Filters: optional date range (updated_at) and type: shop|store|both
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $filterType = $request->input('filter', 'both'); // shop, store, both

        $query = Product::select(
                'id', 'name', 'barcode', 'shop_quantity_in_sales_unit', 'shop_low_stock_margin', 'store_quantity_in_purchase_unit', 'store_low_stock_margin', 'updated_at'
            );

        // Apply date filter on updated_at if provided
        if ($startDate && $endDate) {
            try {
                $s = Carbon::parse($startDate)->startOfDay();
                $e = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('updated_at', [$s, $e]);
            } catch (\Exception $ex) {
                // ignore invalid dates
            }
        }

        // Apply low-stock filter type
        if ($filterType === 'shop') {
            $query->whereColumn('shop_quantity_in_sales_unit', '<=', 'shop_low_stock_margin');
        } elseif ($filterType === 'store') {
            $query->whereColumn('store_quantity_in_purchase_unit', '<=', 'store_low_stock_margin');
        } else {
            $query->where(function($q) {
                $q->whereColumn('shop_quantity_in_sales_unit', '<=', 'shop_low_stock_margin')
                  ->orWhereColumn('store_quantity_in_purchase_unit', '<=', 'store_low_stock_margin');
            });
        }

        $products = $query->orderBy('name')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'barcode' => $item->barcode,
                'shop_quantity' => (int) $item->shop_quantity_in_sales_unit,
                'shop_low_stock_margin' => (int) $item->shop_low_stock_margin,
                'store_quantity' => (int) $item->store_quantity_in_purchase_unit,
                'store_low_stock_margin' => (int) $item->store_low_stock_margin,
                'shop_status' => $item->shop_quantity_in_sales_unit <= $item->shop_low_stock_margin ? 'Low' : 'OK',
                'store_status' => $item->store_quantity_in_purchase_unit <= $item->store_low_stock_margin ? 'Low' : 'OK',
            ];
        });

        $currencySymbol = CompanyInformation::first();

        return Inertia::render('Reports/LowStockReport', [
            'products' => $products,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filter' => $filterType,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    /**
     * Export low stock as CSV (compatible with Excel)
     */
    public function exportLowStockCsv(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $filterType = $request->input('filter', 'both');

        $query = Product::select(
                'id', 'name', 'barcode', 'shop_quantity_in_sales_unit', 'shop_low_stock_margin', 'store_quantity_in_purchase_unit', 'store_low_stock_margin', 'updated_at'
            );

        if ($startDate && $endDate) {
            try {
                $s = Carbon::parse($startDate)->startOfDay();
                $e = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('updated_at', [$s, $e]);
            } catch (\Exception $ex) {
                // ignore invalid dates
            }
        }

        if ($filterType === 'shop') {
            $query->whereColumn('shop_quantity_in_sales_unit', '<=', 'shop_low_stock_margin');
        } elseif ($filterType === 'store') {
            $query->whereColumn('store_quantity_in_purchase_unit', '<=', 'store_low_stock_margin');
        } else {
            $query->where(function($q) {
                $q->whereColumn('shop_quantity_in_sales_unit', '<=', 'shop_low_stock_margin')
                  ->orWhereColumn('store_quantity_in_purchase_unit', '<=', 'store_low_stock_margin');
            });
        }

        $products = $query->orderBy('name')->get();

        $filename = 'low-stock-report-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['ID','Name','Barcode','Shop Qty','Shop Margin','Shop Status','Store Qty','Store Margin','Store Status'];

        $callback = function() use ($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($products as $p) {
                $shopStatus = $p->shop_quantity_in_sales_unit <= $p->shop_low_stock_margin ? 'Low' : 'OK';
                $storeStatus = $p->store_quantity_in_purchase_unit <= $p->store_low_stock_margin ? 'Low' : 'OK';
                fputcsv($file, [
                    $p->id,
                    $p->name,
                    $p->barcode,
                    $p->shop_quantity_in_sales_unit,
                    $p->shop_low_stock_margin,
                    $shopStatus,
                    $p->store_quantity_in_purchase_unit,
                    $p->store_low_stock_margin,
                    $storeStatus,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export low stock as PDF using barryvdh/laravel-dompdf if available
     */
    public function exportLowStockPdf(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $filterType = $request->input('filter', 'both');

        $query = Product::select(
                'id', 'name', 'barcode', 'shop_quantity_in_sales_unit', 'shop_low_stock_margin', 'store_quantity_in_purchase_unit', 'store_low_stock_margin', 'updated_at'
            );

        if ($startDate && $endDate) {
            try {
                $s = Carbon::parse($startDate)->startOfDay();
                $e = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('updated_at', [$s, $e]);
            } catch (\Exception $ex) {
                // ignore invalid dates
            }
        }

        if ($filterType === 'shop') {
            $query->whereColumn('shop_quantity_in_sales_unit', '<=', 'shop_low_stock_margin');
        } elseif ($filterType === 'store') {
            $query->whereColumn('store_quantity_in_purchase_unit', '<=', 'store_low_stock_margin');
        } else {
            $query->where(function($q) {
                $q->whereColumn('shop_quantity_in_sales_unit', '<=', 'shop_low_stock_margin')
                  ->orWhereColumn('store_quantity_in_purchase_unit', '<=', 'store_low_stock_margin');
            });
        }

        $products = $query->orderBy('name')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'barcode' => $item->barcode,
                'shop_quantity' => $item->shop_quantity_in_sales_unit,
                'shop_low_stock_margin' => $item->shop_low_stock_margin,
                'store_quantity' => $item->store_quantity_in_purchase_unit,
                'store_low_stock_margin' => $item->store_low_stock_margin,
                'shop_status' => $item->shop_quantity_in_sales_unit <= $item->shop_low_stock_margin ? 'Low' : 'OK',
                'store_status' => $item->store_quantity_in_purchase_unit <= $item->store_low_stock_margin ? 'Low' : 'OK',
            ];
        });

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('reports.Components.low-stock-pdf', ['products' => $products]);
            return $pdf->download('low-stock-report-' . date('Y-m-d') . '.pdf');
        }

        return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
    }

    // ==================== SHOP LOW STOCK REPORT ====================

    public function lowStockShopReport(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Product::with(['salesUnit'])->select(
                'id', 'name', 'barcode', 'shop_quantity_in_sales_unit', 'shop_low_stock_margin', 'sales_unit_id', 'updated_at'
            );

        if ($startDate && $endDate) {
            try {
                $s = Carbon::parse($startDate)->startOfDay();
                $e = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('updated_at', [$s, $e]);
            } catch (\Exception $ex) {
                // ignore invalid dates
            }
        }

        $query->whereColumn('shop_quantity_in_sales_unit', '<=', 'shop_low_stock_margin');

        $products = $query->orderBy('name')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'barcode' => $item->barcode,
                'shop_quantity' => (int) $item->shop_quantity_in_sales_unit,
                'shop_low_stock_margin' => (int) $item->shop_low_stock_margin,
                'sales_unit' => $item->salesUnit ? $item->salesUnit->name : 'N/A',
                'symbol' => $item->salesUnit ? $item->salesUnit->symbol : 'N/A',
                'status' => $item->shop_quantity_in_sales_unit <= $item->shop_low_stock_margin ? 'Low' : 'OK',
            ];
        });

        $currencySymbol = CompanyInformation::first();

        return Inertia::render('Reports/LowStockShopReport', [
            'products' => $products,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    public function exportLowStockShopCsv(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Product::select(
                'id', 'name', 'barcode', 'shop_quantity_in_sales_unit', 'shop_low_stock_margin', 'updated_at'
            );

        if ($startDate && $endDate) {
            try {
                $s = Carbon::parse($startDate)->startOfDay();
                $e = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('updated_at', [$s, $e]);
            } catch (\Exception $ex) {
                // ignore invalid dates
            }
        }

        $query->whereColumn('shop_quantity_in_sales_unit', '<=', 'shop_low_stock_margin');
        $products = $query->orderBy('name')->get();

        $filename = 'low-stock-shop-report-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['ID','Name','Barcode','Shop Qty','Shop Margin','Status'];

        $callback = function() use ($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($products as $p) {
                $status = $p->shop_quantity_in_sales_unit <= $p->shop_low_stock_margin ? 'Low' : 'OK';
                fputcsv($file, [
                    $p->id,
                    $p->name,
                    $p->barcode,
                    $p->shop_quantity_in_sales_unit,
                    $p->shop_low_stock_margin,
                    $status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportLowStockShopPdf(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Product::with(['salesUnit'])->select(
                'id', 'name', 'barcode', 'shop_quantity_in_sales_unit', 'shop_low_stock_margin', 'sales_unit_id', 'updated_at'
            );

        if ($startDate && $endDate) {
            try {
                $s = Carbon::parse($startDate)->startOfDay();
                $e = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('updated_at', [$s, $e]);
            } catch (\Exception $ex) {
                // ignore invalid dates
            }
        }

        $query->whereColumn('shop_quantity_in_sales_unit', '<=', 'shop_low_stock_margin');
        $products = $query->orderBy('name')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'barcode' => $item->barcode,
                'shop_quantity' => $item->shop_quantity_in_sales_unit,
                'shop_low_stock_margin' => $item->shop_low_stock_margin,
                'sales_unit' => $item->salesUnit ? $item->salesUnit->name : 'N/A',
                'symbol' => $item->salesUnit ? $item->salesUnit->symbol : 'N/A',
                'status' => $item->shop_quantity_in_sales_unit <= $item->shop_low_stock_margin ? 'Low' : 'OK',
            ];
        });

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('reports.Components.low-stock-shop-pdf', [
                'products' => $products,
                'startDate' => $startDate,
                'endDate' => $endDate
            ]);
            return $pdf->download('low-stock-shop-report-' . date('Y-m-d') . '.pdf');
        }

        return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
    }

    // ==================== STORE LOW STOCK REPORT ====================

    public function lowStockStoreReport(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Product::with(['salesUnit'])->select(
                'id', 'name', 'barcode', 'store_quantity_in_purchase_unit', 'store_low_stock_margin', 'sales_unit_id', 'updated_at'
            );

        if ($startDate && $endDate) {
            try {
                $s = Carbon::parse($startDate)->startOfDay();
                $e = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('updated_at', [$s, $e]);
            } catch (\Exception $ex) {
                // ignore invalid dates
            }
        }

        $query->whereColumn('store_quantity_in_purchase_unit', '<=', 'store_low_stock_margin');

        $products = $query->orderBy('name')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'barcode' => $item->barcode,
                'store_quantity' => (int) $item->store_quantity_in_purchase_unit,
                'store_low_stock_margin' => (int) $item->store_low_stock_margin,
                'sales_unit' => $item->salesUnit ? $item->salesUnit->name : 'N/A',
                'symbol' => $item->salesUnit ? $item->salesUnit->symbol : 'N/A',
                'status' => $item->store_quantity_in_purchase_unit <= $item->store_low_stock_margin ? 'Low' : 'OK',
            ];
        });

        $currencySymbol = CompanyInformation::first();

        return Inertia::render('Reports/LowStockStoreReport', [
            'products' => $products,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    public function exportLowStockStoreCsv(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Product::select(
                'id', 'name', 'barcode', 'store_quantity_in_purchase_unit', 'store_low_stock_margin', 'updated_at'
            );

        if ($startDate && $endDate) {
            try {
                $s = Carbon::parse($startDate)->startOfDay();
                $e = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('updated_at', [$s, $e]);
            } catch (\Exception $ex) {
                // ignore invalid dates
            }
        }

        $query->whereColumn('store_quantity_in_purchase_unit', '<=', 'store_low_stock_margin');
        $products = $query->orderBy('name')->get();

        $filename = 'low-stock-store-report-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['ID','Name','Barcode','Store Qty','Store Margin','Status'];

        $callback = function() use ($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($products as $p) {
                $status = $p->store_quantity_in_purchase_unit <= $p->store_low_stock_margin ? 'Low' : 'OK';
                fputcsv($file, [
                    $p->id,
                    $p->name,
                    $p->barcode,
                    $p->store_quantity_in_purchase_unit,
                    $p->store_low_stock_margin,
                    $status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportLowStockStorePdf(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Product::with(['salesUnit'])->select(
                'id', 'name', 'barcode', 'store_quantity_in_purchase_unit', 'store_low_stock_margin', 'sales_unit_id', 'updated_at'
            );

        if ($startDate && $endDate) {
            try {
                $s = Carbon::parse($startDate)->startOfDay();
                $e = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('updated_at', [$s, $e]);
            } catch (\Exception $ex) {
                // ignore invalid dates
            }
        }

        $query->whereColumn('store_quantity_in_purchase_unit', '<=', 'store_low_stock_margin');
        $products = $query->orderBy('name')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'barcode' => $item->barcode,
                'store_quantity' => $item->store_quantity_in_purchase_unit,
                'store_low_stock_margin' => $item->store_low_stock_margin,
                'sales_unit' => $item->salesUnit ? $item->salesUnit->name : 'N/A',
                'symbol' => $item->salesUnit ? $item->salesUnit->symbol : 'N/A',
                'status' => $item->store_quantity_in_purchase_unit <= $item->store_low_stock_margin ? 'Low' : 'OK',
            ];
        });

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('reports.Components.low-stock-store-pdf', [
                'products' => $products,
                'startDate' => $startDate,
                'endDate' => $endDate
            ]);
            return $pdf->download('low-stock-store-report-' . date('Y-m-d') . '.pdf');
        }

        return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
    }

    /**
     * Helper: Build GRN data for export/display
     *
     * Aggregates GRN information with eager loading of relationships:
     * - Supplier details
     * - Products with quantities and pricing
     * - Calculated totals (subtotal, discount, tax, grand total)
     *
     * @param string $startDate - Start of date range (Y-m-d format)
     * @param string $endDate - End of date range (Y-m-d format)
     * @return array Contains 'rows' and 'totals' keys with aggregated data
     */
    private function buildGoodsReceivedNoteData(string $startDate, string $endDate): array
    {
        // Eager load related data to optimize query performance
        $grns = GoodsReceivedNote::with(['goods_received_note_products.product', 'supplier:id,name'])
            ->whereBetween('goods_received_note_date', [$startDate, $endDate])
            ->orderByDesc('goods_received_note_date')
            ->get();

        $rows = $grns->map(function ($grn) {
            $grossTotal = $grn->goods_received_note_products->reduce(function ($carry, $item) {
                return $carry + ((float) $item->quantity * (float) $item->purchase_price);
            }, 0);

            $lineDiscount = $grn->goods_received_note_products->sum('discount');
            $productsTotal = $grn->goods_received_note_products->sum('total');
            $headerDiscount = (float) ($grn->discount ?? 0);
            $taxTotal = (float) ($grn->tax_total ?? 0);
            $netTotal = $productsTotal - $headerDiscount + $taxTotal;

            return [
                'id' => $grn->id,
                'grn_no' => $grn->goods_received_note_no,
                'supplier_name' => $grn->supplier->name ?? 'N/A',
                'date' => $grn->goods_received_note_date,
                'items_count' => $grn->goods_received_note_products->sum('quantity'),
                'items' => $grn->goods_received_note_products->map(function ($item) {
                    return [
                        'name' => $item->product->name ?? 'N/A',
                        'quantity' => (float) $item->quantity,
                        'purchase_price' => (float) $item->purchase_price,
                        'total' => (float) $item->total,
                    ];
                })->values(),
                'gross_total' => round($grossTotal, 2),
                'line_discount' => round($lineDiscount, 2),
                'header_discount' => round($headerDiscount, 2),
                'tax_total' => round($taxTotal, 2),
                'net_total' => round($netTotal, 2),
                'status' => $grn->status,
            ];
        });

        $totals = [
            'count' => $rows->count(),
            'items_count' => $rows->sum('items_count'),
            'gross_total' => number_format($rows->sum('gross_total'), 2),
            'net_total' => number_format($rows->sum('net_total'), 2),
            'tax_total' => number_format($rows->sum('tax_total'), 2),
            'discount_total' => number_format($rows->sum('line_discount') + $rows->sum('header_discount'), 2),
        ];

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * Helper: Build GRN Return data for export/display
     *
     * Aggregates return information with:
     * - Original GRN reference
     * - Returned products with quantities
     * - Estimated value calculations based on purchase prices
     * - User who handled the return
     *
     * @param string $startDate - Start of date range (Y-m-d format)
     * @param string $endDate - End of date range (Y-m-d format)
     * @return array Contains 'rows' and 'totals' keys with return data
     */
    private function buildGrnReturnData(string $startDate, string $endDate): array
    {
        // Eager load related data
        $returns = GoodsReceivedNoteReturn::with([
                'goodsReceivedNote:id,goods_received_note_no',
                'goodsReceivedNoteReturnProducts.product:id,name,purchase_price',
                'user:id,name',
            ])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderByDesc('date')
            ->get();

        $priceLookup = GoodsReceivedNoteProduct::whereIn('goods_received_note_id', $returns->pluck('goods_received_note_id'))
            ->get()
            ->groupBy(function ($row) {
                return $row->goods_received_note_id . '-' . $row->product_id;
            });

        $rows = $returns->map(function ($return) use ($priceLookup) {
            $lineItems = [];
            $totalQty = 0;
            $estimatedValue = 0;

            foreach ($return->goodsReceivedNoteReturnProducts as $item) {
                $key = $return->goods_received_note_id . '-' . $item->product_id;
                $purchasePrice = optional(optional($priceLookup->get($key))[0])->purchase_price;

                if ($purchasePrice === null && $item->relationLoaded('product')) {
                    $purchasePrice = $item->product->purchase_price ?? 0;
                }

                $lineTotal = ((float) $item->quantity) * ((float) ($purchasePrice ?? 0));
                $totalQty += (float) $item->quantity;
                $estimatedValue += $lineTotal;

                $lineItems[] = [
                    'product_name' => $item->product->name ?? 'N/A',
                    'quantity' => (float) $item->quantity,
                    'estimated_value' => round($lineTotal, 2),
                ];
            }

            return [
                'id' => $return->id,
                'grn_no' => $return->goodsReceivedNote->goods_received_note_no ?? null,
                'date' => $return->date->format('Y-m-d'),
                'handled_by' => $return->user->name ?? 'N/A',
                'total_quantity' => round($totalQty, 2),
                'estimated_value' => round($estimatedValue, 2),
                'items' => $lineItems,
            ];
        });

        $totals = [
            'count' => $rows->count(),
            'quantity' => $rows->sum('total_quantity'),
            'estimated_value' => number_format($rows->sum('estimated_value'), 2),
        ];

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * Helper: Build Product Movement data for export/display
     *
     * Compiles inventory movement records with:
     * - Movement type mapping (purchase, sale, transfer, returns)
     * - Product details (name, code)
     * - Summary statistics by movement type
     * - Inbound/Outbound totals for net movement calculation
     *
     * @param string $startDate - Start of date range (Y-m-d format)
     * @param string $endDate - End of date range (Y-m-d format)
     * @param int|null $productId - Optional product filter
     * @return array Contains 'movements', 'summaryByType', 'totals', and 'productName'
     */
    private function buildProductMovementData($startDate, $endDate, $productId = null)
    {
        // Map numeric movement types to readable strings
        $movementTypes = [
            ProductMovement::TYPE_PURCHASE => 'purchase',
            ProductMovement::TYPE_PURCHASE_RETURN => 'purchase_return',
            ProductMovement::TYPE_TRANSFER => 'transfer',
            ProductMovement::TYPE_SALE => 'sale',
            ProductMovement::TYPE_SALE_RETURN => 'sale_return',
            ProductMovement::TYPE_GRN_RETURN => 'grn_return',
            ProductMovement::TYPE_STOCK_TRANSFER_RETURN => 'stock_transfer_return',
        ];

        $query = ProductMovement::with('product')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $movements = $query->orderByDesc('created_at')->get();

        $movementRecords = $movements->map(function ($movement) use ($movementTypes) {
            return [
                'id' => $movement->id,
                'movement_date' => $movement->created_at,
                'date_only' => $movement->created_at->format('Y-m-d'),
                'product_name' => $movement->product->name ?? 'Unknown',
                'product_code' => $movement->product->barcode ?? 'N/A',
                'movement_type' => $movementTypes[$movement->movement_type] ?? 'unknown',
                'quantity' => $movement->quantity,
                'reference' => $movement->reference ?? 'N/A',
            ];
        });

        // Calculate summary by type
        $summaryByType = $movements->groupBy('movement_type')->map(function ($group, $typeId) use ($movementTypes) {
            return [
                'type_name' => $movementTypes[$typeId] ?? 'unknown',
                'count' => $group->count(),
                'total_quantity' => $group->sum('quantity'),
            ];
        });

        // Calculate totals based on type IDs
        $inboundTypes = [
            ProductMovement::TYPE_PURCHASE,
            ProductMovement::TYPE_SALE_RETURN,
            ProductMovement::TYPE_GRN_RETURN,
        ];
        $outboundTypes = [
            ProductMovement::TYPE_SALE,
            ProductMovement::TYPE_TRANSFER,
            ProductMovement::TYPE_PURCHASE_RETURN,
            ProductMovement::TYPE_STOCK_TRANSFER_RETURN,
        ];

        $totalInbound = $movements->whereIn('movement_type', $inboundTypes)->sum('quantity');
        $totalOutbound = $movements->whereIn('movement_type', $outboundTypes)->sum('quantity');

        $productName = null;
        if ($productId) {
            $product = Product::find($productId);
            $productName = $product ? $product->name : 'Unknown Product';
        }

        return [
            'movements' => $movementRecords,
            'summaryByType' => $summaryByType,
            'totals' => [
                'inbound' => $totalInbound,
                'outbound' => $totalOutbound,
                'net' => $totalInbound - $totalOutbound,
            ],
            'productName' => $productName,
        ];
    }
}
