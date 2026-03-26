<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ActivityLogReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $userId = $request->input('user_id');
        $module = $request->input('module');

        Log::info('ActivityLogReportController@index request', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'user_id' => $userId,
            'module' => $module,
        ]);

        $query = ActivityLog::with('user')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($module) {
            $query->where('module', $module);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Transform the paginated collection
        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'user_name' => $log->user->name ?? 'N/A',
                'action' => $log->action,
                'module' => $log->module,
                'details' => $log->details,
                'created_at' => $log->created_at->timezone('Asia/Colombo')->toDateTimeString(),
            ];
        });

        Log::info('ActivityLogReportController@index SQL', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        $users = User::select('id', 'name')->get();
        $modules = ActivityLog::select('module')->distinct()->pluck('module');

        return Inertia::render('Reports/ActivityLogReport', [
            'logs' => $logs,
            'users' => $users,
            'modules' => $modules,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedUser' => $userId,
            'selectedModule' => $module,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $userId = $request->input('user_id');
        $module = $request->input('module');

        $query = ActivityLog::with('user')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($module) {
            $query->where('module', $module);
        }

        // Temporarily increase memory limit for large datasets
        ini_set('memory_limit', '512M');

        $logs = $query->orderBy('created_at', 'desc')->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'user_name' => $log->user->name ?? 'N/A',
                'action' => $log->action,
                'module' => $log->module,
                'details' => $log->details,
                'created_at' => $log->created_at->timezone('Asia/Colombo')->toDateTimeString(),
            ];
        });

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('reports.Components.activity-log-pdf', [
                'logs' => $logs,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'selectedUser' => $userId ? User::find($userId)?->name : 'All Users',
                'selectedModule' => $module ?: 'All Modules',
            ]);
            return $pdf->download('activity-log-report-' . date('Y-m-d') . '.pdf');
        }

        return back()->with('error', 'PDF export not available. Install barryvdh/laravel-dompdf package.');
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $userId = $request->input('user_id');
        $module = $request->input('module');

        $query = ActivityLog::with('user')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($module) {
            $query->where('module', $module);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        return response()->stream(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            
            // CSV header
            fputcsv($handle, ['ID', 'Date & Time', 'User', 'Module', 'Action', 'Details']);
            
            // CSV rows
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at->timezone('Asia/Colombo')->toDateTimeString(),
                    $log->user->name ?? 'N/A',
                    $log->module,
                    $log->action,
                    $log->details,
                ]);
            }
            
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="activity-log-report-' . date('Y-m-d') . '.csv"',
        ]);
    }
}
