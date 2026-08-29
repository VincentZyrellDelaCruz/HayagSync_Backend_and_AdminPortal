<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SecurityEvent;
use App\Models\UserLoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SecurityCenterController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'activity');
        $search = trim((string) $request->get('search', ''));
        $severity = $request->get('severity');
        $status = $request->get('status');

        // ACTIVITY LOGS (USER ACTIVITIES LIKE SUBMIT REPORT, AND CHANGE UPDATES)
        $activityLogs = ActivityLog::query()
            ->with('user:id,first_name,last_name,email')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('action_type', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('record_id', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(12, ['*'], 'activity_page')
            ->withQueryString();


        // LOGIN HISTORY (TRACKS LOGIN METADATA)
        $loginHistory = UserLoginHistory::query()
            ->with('user:id,first_name,last_name,email')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('device_name', 'like', "%{$search}%")
                        ->orWhere('browser', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('login_time')
            ->paginate(12, ['*'], 'login_page')
            ->withQueryString();

        // SECURITY EVENTS
        $securityEvents = SecurityEvent::query()
            ->with([
                'user:id,first_name,last_name,email',
                'admin.user:id,first_name,last_name,email',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('event_type', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($severity, function ($query) use ($severity) {
                $query->where('severity', $severity);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderByRaw("
                CASE severity
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                    ELSE 5
                END
            ")
            ->latest()
            ->paginate(12, ['*'], 'security_page')
            ->withQueryString();

        // SUMMARY COUNTS
        $summary = [
            'activity' => ActivityLog::count(),

            'recent_logins' => UserLoginHistory::where(
                'login_time',
                '>=',
                now()->subDays(7)
            )->count(),

            'open_security_events' => SecurityEvent::where(
                'status',
                '!=',
                'resolved'
            )->count(),

            'critical_security_events' => SecurityEvent::where(
                'severity',
                'critical'
            )
                ->where('status', '!=', 'resolved')
                ->count(),
        ];

        return Inertia::render(
            'Admin/Security/Index',
            [
                'activityLogs' => $activityLogs,
                'loginHistory' => $loginHistory,
                'securityEvents' => $securityEvents,
                'summary' => $summary,
                'tab' => $tab,
                'search' => $search,
                'severity' => $severity,
                'status' => $status,
            ]
        );
    }

    public function resolve(Request $request, SecurityEvent $securityEvent)
    {
        $admin = Auth::user();

        abort_unless(
            $admin?->staff?->is_admin,
            403,
            'Unauthorized access.'
        );

        DB::transaction(function () use (
            $securityEvent,
            $admin,
            $request
        ) {
            $securityEvent->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => $admin->staff->getKey(),
            ]);

            ActivityLog::create([
                'user_id' => $admin->id,
                'action_type' => 'security_event_resolved',
                'description' => sprintf(
                    'Resolved security event: %s',
                    $securityEvent->event_type
                ),
                'module' => 'Security Center',
                'record_id' => $securityEvent->id,
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with(
            'success',
            'Security event marked as resolved.'
        );
    }
}
