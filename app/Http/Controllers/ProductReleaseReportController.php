<?php

namespace App\Http\Controllers;

use App\Models\ProductReleaseNote;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductReleaseReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $releases = ProductReleaseNote::with(['user', 'product_transfer_request', 'product_release_note_products.product', 'product_release_note_products.unit_id'])
            ->whereBetween('release_date', [$startDate, $endDate])
            ->orderBy('release_date', 'desc')
            ->get()
            ->map(function ($release) {
                return [
                    'id' => $release->id,
                    'release_date' => $release->release_date,
                    'product_transfer_request_no' => $release->product_transfer_request->product_transfer_request_no ?? 'N/A',
                    'user_name' => $release->user->name ?? 'N/A',
                    'status' => $release->status,
                    'total_items' => $release->product_release_note_products->sum('quantity'),
                    'products' => $release->product_release_note_products->map(function ($prnProduct) {
                        return [
                            'product_name' => $prnProduct->product->name ?? 'N/A',
                            'quantity' => $prnProduct->quantity,
                        ];
                    }),
                ];
            });

        return Inertia::render('Reports/ProductReleaseReport', [
            'releases' => $releases,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $releases = ProductReleaseNote::with(['user', 'product_transfer_request', 'product_release_note_products.product', 'product_release_note_products.unit_id'])
            ->whereBetween('release_date', [$startDate, $endDate])
            ->orderBy('release_date', 'desc')
            ->get()
            ->map(function ($release) {
                return [
                    'id' => $release->id,
                    'release_date' => $release->release_date,
                    'product_transfer_request_no' => $release->product_transfer_request->product_transfer_request_no ?? 'N/A',
                    'user_name' => $release->user->name ?? 'N/A',
                    'status' => $release->status,
                    'status_name' => $release->status == 1 ? 'Approved' : 'Pending',
                    'total_items' => $release->product_release_note_products->sum('quantity'),
                    'products' => $release->product_release_note_products->map(function ($prnProduct) {
                        return [
                            'product_name' => $prnProduct->product->name ?? 'N/A',
                            'quantity' => $prnProduct->quantity,
                        ];
                    }),
                ];
            });

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('reports.Components.product-release-pdf', [
                'releases' => $releases,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]);
            return $pdf->download('product-release-report-' . date('Y-m-d') . '.pdf');
        }

        return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $releases = ProductReleaseNote::with(['user', 'product_transfer_request', 'product_release_note_products.product', 'product_release_note_products.unit_id'])
            ->whereBetween('release_date', [$startDate, $endDate])
            ->orderBy('release_date', 'desc')
            ->get();

        return response()->stream(function () use ($releases) {
            $handle = fopen('php://output', 'w');
            
            // CSV header
            fputcsv($handle, ['Release Date', 'Transfer Request No', 'User', 'Status', 'Product Name', 'Quantity']);
            
            // CSV rows
            foreach ($releases as $release) {
                $statusName = $release->status == 1 ? 'Approved' : 'Pending';
                foreach ($release->product_release_note_products as $product) {
                    fputcsv($handle, [
                        $release->release_date,
                        $release->product_transfer_request->product_transfer_request_no ?? 'N/A',
                        $release->user->name ?? 'N/A',
                        $statusName,
                        $product->product->name ?? 'N/A',
                        $product->quantity,
                    ]);
                }
            }
            
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product-release-report-' . date('Y-m-d') . '.csv"',
        ]);
    }
}
