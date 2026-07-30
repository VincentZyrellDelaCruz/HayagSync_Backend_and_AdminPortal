<?php

namespace App\Services;

use Google\Client;
use Google\Service\FirebaseCloudMessaging;
use Google\Service\FirebaseCloudMessaging\SendMessageRequest;
use Google\Service\FirebaseCloudMessaging\Message;
use Google\Service\FirebaseCloudMessaging\Notification;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected FirebaseCloudMessaging $messaging;
    protected string $projectId;

    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(config('services.fcm.credentials'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $this->messaging = new FirebaseCloudMessaging($client);
        $this->projectId = config('services.fcm.project_id');
    }

    /**
     * Send a notification to one or more device tokens.
     *
     * @param array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     */
    public function send(array $tokens, string $title, string $body, array $data = []): void
    {
        foreach ($tokens as $token) {
            // Build Notification object
            $notification = new Notification();
            $notification->setTitle($title);
            $notification->setBody($body);

            // Build Message object
            $message = new Message();
            $message->setToken($token);
            $message->setNotification($notification);
            $message->setData($data);

            // Wrap in SendMessageRequest
            $request = new SendMessageRequest();
            $request->setMessage($message);

            // Send via FCM
            $response = $this->messaging->projects_messages->send(
                'projects/' . $this->projectId,
                $request
            );

            Log::info('FCM response', (array) $response);

        }
    }
}
