<?php

namespace App\Services;

use App\Events\InAppNotificationCreated;
use App\Models\Inbox;
use App\Models\User;

class NotificationService
{
    public static function send(
        User $receiver,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?User $sender = null,
        string $priority = 'normal',
        array $data = [],
        int $expiresInDays = 30
    ): Inbox {
        $notification = Inbox::create([
            'sender_id' => $sender?->id,
            'receiver_id' => $receiver->id,
            'title' => $title,
            'message' => $message,
            'notification_type' => $type,
            'priority' => $priority,
            'action_url' => $actionUrl,
            'data' => $data,
            'is_read' => false,
            'read_at' => null,
            'expires_at' => now()->addDays($expiresInDays),
        ]);

        InAppNotificationCreated::dispatch(
            (string) $receiver->id,
            [
                'id' => $notification->id,
                'type' => $notification->notification_type,
                'title' => $notification->title,
                'message' => $notification->message,
                'priority' => $notification->priority,
                'action_url' => $notification->action_url,
                'data' => $notification->data ?? [],
                'is_read' => false,
                'created_at' => $notification->created_at?->toIso8601String(),
                'expires_at' => $notification->expires_at?->toIso8601String(),
            ]
        );

        return $notification;
    }
}
