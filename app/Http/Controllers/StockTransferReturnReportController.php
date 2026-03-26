<?php

namespace App\Http\Controllers;

use App\Models\StockTransferReturn;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class StockTransferReturnReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $returns = StockTransferReturn::with(['user', 'products.product', 'products.measurementUnit'])
            ->whereBetween('return_date', [$startDate, $endDate])
            ->orderBy('return_date', 'desc')
            ->get()
            ->map(function ($return) {
                return [
                    'id' => $return->id,
                    'return_date' => $return->return_date,
                    'return_no' => $return->return_no,
                    'user_name' => $return->user->name ?? 'N/A',
                    'status' => $return->status,
                    'total_items' => $return->products->sum('stock_transfer_quantity'),
                    'reason' => $return->reason,
                    'products' => $return->products->map(function ($product) {
                        return [
                            'product_name' => $product->product->name ?? 'N/A',
                            'stock_transfer_quantity' => $product->stock_transfer_quantity,
                            'measurement_unit' => $product->measurementUnit->name ?? 'N/A',
                        ];
                    }),
                ];
            });

        return Inertia::render('Reports/StockTransferReturnReport', [
            'returns' => $returns,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $returns = StockTransferReturn::with(['user', 'products.product', 'products.measurementUnit'])
            ->whereBetween('return_date', [$startDate, $endDate])
            ->orderBy('return_date', 'desc')
            ->get()
            ->map(function ($return) {
                return [
                    'id' => $return->id,
                    'return_date' => $return->return_date,
                    'return_no' => $return->return_no,
                    'user_name' => $return->user->name ?? 'N/A',
                    'status' => $return->status,
                    'status_name' => $return->status == 1 ? 'Approved' : 'Pending',
                    'total_items' => $return->products->sum('stock_transfer_quantity'),
                    'reason' => $return->reason ?? 'N/A',
                    'products' => $return->products->map(function ($product) {
                        return [
                            'product_name' => $product->product->name ?? 'N/A',
                            'stock_transfer_quantity' => $product->stock_transfer_quantity,
                            'measurement_unit' => $product->measurementUnit->name ?? 'N/A',
                        ];
                    }),
                ];
            });

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('reports.Components.stock-transfer-return-pdf', [
                'returns' => $returns,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]);
            return $pdf->download('stock-transfer-return-report-' . date('Y-m-d') . '.pdf');
        }

        return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $returns = StockTransferReturn::with(['user', 'products.product', 'products.measurementUnit'])
            ->whereBetween('return_date', [$startDate, $endDate])
            ->orderBy('return_date', 'desc')
            ->get();

        return response()->stream(function () use ($returns) {
            $handle = fopen('php://output', 'w');
            
            // CSV header
            fputcsv($handle, ['Return Date', 'Return No', 'User', 'Status', 'Reason', 'Product Name', 'Quantity', 'Unit']);
            
            // CSV rows
            foreach ($returns as $return) {
                $statusName = $return->status == 1 ? 'Approved' : 'Pending';
                foreach ($return->products as $product) {
                    fputcsv($handle, [
                        $return->return_date,
                        $return->return_no,
                        $return->user->name ?? 'N/A',
                        $statusName,
                        $return->reason ?? 'N/A',
                        $product->product->name ?? 'N/A',
                        $product->stock_transfer_quantity,
                        $product->measurementUnit->name ?? 'N/A',
                    ]);
                }
            }
            
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="stock-transfer-return-report-' . date('Y-m-d') . '.csv"',
        ]);
    }
}
