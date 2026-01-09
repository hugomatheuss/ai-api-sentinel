<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

/**
 * Activity logs web controller.
 *
 * Displays activity logs in the web interface for monitoring.
 */
class ActivityLogController extends Controller
{
    /**
     * Display activity logs
     */
    public function index(Request $request)
    {
        $query = ActivityLog::query()
            ->orderBy('created_at', 'desc');

        // Filter by log name
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        // Filter by event
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Paginate
        $logs = $query->paginate(50);

        // Get available filters
        $logNames = ActivityLog::distinct()->pluck('log_name')->filter();
        $events = ActivityLog::whereNotNull('event')->distinct()->pluck('event')->filter();

        return view('activity-logs.index', compact('logs', 'logNames', 'events'));
    }

    /**
     * Show activity log details
     */
    public function show(ActivityLog $log)
    {
        return view('activity-logs.show', compact('log'));
    }
}
