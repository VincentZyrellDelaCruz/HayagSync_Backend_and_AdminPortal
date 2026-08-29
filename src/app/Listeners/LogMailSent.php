<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogMailSent
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
    public function handle(MessageSent $event): void
    {
        Log::info('Email sent.', [
            'subject' => $event->message->getSubject(),
            'to' => collect($event->message->getTo())
                ->map(fn ($address) => $address->getAddress())
                ->values()
                ->all(),
            /* 'message_id' => $event->message->getId(), */
        ]);
    }
}
