<?php

namespace App\Listeners;

use App\Mail\SecurityAlertMail;
use App\Models\ActivityLog;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class LogFailedLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        $email = strtolower(trim((string) ($event->credentials['email'] ?? 'unknown')));

        $ipAddress = request()->ip();

        // LOGS NEW USER ACTIVITY
        ActivityLog::create([
            'user_id'    => optional($event->user)->id, // may be null if user not found
            'action_type'=> 'login_failed',
            'description'=> 'Login attempt failed for email: '.$email,
            'module'     => 'auth',
            'ip_address' => request()->ip(),
        ]);

        // LOGIN COUNTER
        $accountKey = 'security:failed-login:account:' . hash('sha256',$email);
        $ipKey = 'security:failed-login:ip:' . hash('sha256', (string) $ipAddress);
        $ttl = now()->addMinutes(10);

        Cache::add($accountKey, 0, $ttl);
        Cache::add($ipKey, 0, $ttl);

        $accountAttempts = Cache::increment($accountKey);
        $ipAttempts = Cache::increment($ipKey);

        /*
        | Account-based detection:
        | 5  = High
        | 10 = Critical
        |
        | IP-based detection:
        | 10 = High
        | 20 = Critical
        */
        $severity = null;
        $threshold = null;

        if ($accountAttempts >= 10 || $ipAttempts >= 20) {
            $severity = 'critical';
            $threshold = max($accountAttempts,$ipAttempts);
        } elseif ($accountAttempts >= 5 || $ipAttempts >= 10) {
            $severity = 'high';
            $threshold = max($accountAttempts,$ipAttempts);
        }

        if (!$severity) return;


        // Prevent repeated events during the same attack window
        $eventKey = 'security:failed-login:event:' . hash(
            'sha256',
            $email . '|' . ($ipAddress ?? '')
        ) . ':' . $severity;

        if (!Cache::add($eventKey, true, $ttl)) return;

        // CREATE SECURITY EVENT
        $securityEvent = SecurityEvent::create([
            'user_id' => optional($event->user)->id,
            'severity' => $severity,
            'event_type' => 'Repeated Failed Login Attempts',
            'description' => sprintf(
                'Repeated failed login attempts detected for account "%s" from IP address %s. %d failed attempts were detected within the configured monitoring window.',
                $email,
                $ipAddress ?? 'Unknown',
                $threshold
            ),
            'ip_address' => $ipAddress,
            'location' => null,
            'status' => 'open',
        ]);

        // NOTIFY ADMINISTRATOR
        $admins = User::query()
            ->whereHas('staff', function ($query) {
                $query->where('is_admin', true);
            })
            ->whereNotNull('email')
            ->get();

        foreach ($admins as $admin) {
            // EMAIL
            Mail::to($admin->email)->queue(
                new SecurityAlertMail(
                    $securityEvent,
                    $admin
                )
            );

            // IN-APP NOTIFICATION
            NotificationService::send(
                receiver: $admin,
                type: 'security_alert',
                title: strtoupper($severity) . ' Security Alert',
                message: 'A security event requiring administrative review has been detected.',
                actionUrl: route(
                    'web.admin.security.index',
                    [
                        'tab' => 'security',
                        'severity' => $severity,
                        'status' => 'open',
                    ]
                ),
                priority: $severity,
                data: [
                    'security_event_id' =>
                        (string) $securityEvent->id,
                    'severity' => $severity,
                ]
            );
        }
    }
}
