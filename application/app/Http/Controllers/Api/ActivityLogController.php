<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

/**
 * Activity logs controller.
 *
 * Provides access to system activity logs for monitoring and audit.
 */
class ActivityLogController extends Controller
{
    /**
     * List activity logs
     */
    public function index(Request $request)
    {
        $query = ActivityLog::query()
            ->orderBy('created_at', 'desc');

        // Filter by log name
        if ($request->has('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        // Filter by event
        if ($request->has('event')) {
            $query->where('event', $request->event);
        }

        // Filter by date range
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        // Pagination
        $perPage = min($request->get('per_page', 50), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'logs' => $logs->items(),
            'pagination' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    /**
     * Get activity statistics
     */
    public function stats(Request $request)
    {
        $from = $request->get('from', now()->subDays(7));
        $to = $request->get('to', now());

        $stats = [
            'total_activities' => ActivityLog::whereBetween('created_at', [$from, $to])->count(),
            'by_log_name' => ActivityLog::whereBetween('created_at', [$from, $to])
                ->selectRaw('log_name, count(*) as count')
                ->groupBy('log_name')
                ->get()
                ->pluck('count', 'log_name'),
            'by_event' => ActivityLog::whereBetween('created_at', [$from, $to])
                ->selectRaw('event, count(*) as count')
                ->whereNotNull('event')
                ->groupBy('event')
                ->get()
                ->pluck('count', 'event'),
            'recent_activities' => ActivityLog::orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($log) {
                    return [
                        'description' => $log->description,
                        'log_name' => $log->log_name,
                        'event' => $log->event,
                        'created_at' => $log->created_at->toIso8601String(),
                    ];
                }),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}

