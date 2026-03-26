<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class SyncReportController extends Controller
{
    /**
     * Display a listing of the sync logs from syn_logs table.
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $userId = $request->input('user_id');

        $query = \DB::table('syn_logs')
            ->leftJoin('users', 'syn_logs.user_id', '=', 'users.id')
            ->select(
                'syn_logs.id',
                'syn_logs.table_name',
                'syn_logs.module',
                'syn_logs.action',
                'syn_logs.synced_at',
                'syn_logs.user_id',
                'users.name as user_name'
            );

        if ($startDate) {
            $query->whereDate('syn_logs.synced_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('syn_logs.synced_at', '<=', $endDate);
        }
        if ($userId) {
            $query->where('syn_logs.user_id', $userId);
        }

        $logs = $query->orderByDesc('syn_logs.synced_at')
            ->paginate(10)
            ->withQueryString();

        // Transform the paginated collection
        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'user_name' => $log->user_name ?? 'System',
                'action' => $log->action,
                'module' => $log->module ?? $log->table_name,
                'table_name' => $log->table_name,
                'created_at' => $log->synced_at,
            ];
        });

        // Get unique user IDs from syn_logs
        $userIds = \DB::table('syn_logs')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->all();
        $users = \App\Models\User::whereIn('id', $userIds)->get(['id', 'name']);

        return Inertia::render('Reports/SyncReport', [
            'logs' => $logs,
            'users' => $users,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedUser' => $userId,
        ]);
    }

    /**
     * Export sync report as PDF from syn_logs table
     */
    public function exportPdf(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $userId = $request->input('user_id');

        $query = \DB::table('syn_logs')
            ->leftJoin('users', 'syn_logs.user_id', '=', 'users.id')
            ->select(
                'syn_logs.id',
                'syn_logs.table_name',
                'syn_logs.module',
                'syn_logs.action',
                'syn_logs.synced_at',
                'syn_logs.user_id',
                'users.name as user_name'
            );

        if ($startDate) {
            $query->whereDate('syn_logs.synced_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('syn_logs.synced_at', '<=', $endDate);
        }
        if ($userId) {
            $query->where('syn_logs.user_id', $userId);
        }

        // Temporarily increase memory limit for large datasets
        ini_set('memory_limit', '512M');

        $logs = $query->orderBy('syn_logs.synced_at', 'desc')->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'user_name' => $log->user_name ?? 'System',
                'action' => $log->action,
                'module' => $log->module ?? $log->table_name,
                'table_name' => $log->table_name,
                'created_at' => $log->synced_at,
            ];
        });

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('reports.Components.sync-pdf', [
                'logs' => $logs,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'selectedUser' => $userId ? User::find($userId)?->name : 'All Users',
            ]);
            return $pdf->download('sync-report-' . date('Y-m-d') . '.pdf');
        }

        return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
    }

    /**
     * Export sync report as Excel/CSV from syn_logs table
     */
    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $userId = $request->input('user_id');

        $query = \DB::table('syn_logs')
            ->leftJoin('users', 'syn_logs.user_id', '=', 'users.id')
            ->select(
                'syn_logs.id',
                'syn_logs.table_name',
                'syn_logs.module',
                'syn_logs.action',
                'syn_logs.synced_at',
                'syn_logs.user_id',
                'users.name as user_name'
            );

        if ($startDate) {
            $query->whereDate('syn_logs.synced_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('syn_logs.synced_at', '<=', $endDate);
        }
        if ($userId) {
            $query->where('syn_logs.user_id', $userId);
        }

        $logs = $query->orderBy('syn_logs.synced_at', 'desc')->get();

        return response()->stream(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            
            // CSV header
            fputcsv($handle, ['ID', 'Date & Time', 'User', 'Table Name', 'Module', 'Action']);
            
            // CSV rows
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->synced_at,
                    $log->user_name ?? 'System',
                    $log->table_name,
                    $log->module ?? $log->table_name,
                    $log->action,
                ]);
            }
            
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sync-report-' . date('Y-m-d') . '.csv"',
        ]);
    }
}
