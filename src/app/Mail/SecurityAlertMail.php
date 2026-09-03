<?php

namespace App\Mail;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SecurityAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(
        public SecurityEvent $securityEvent,
        public User $admin
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: strtoupper(
                (string) $this->securityEvent->severity
            ) . ' Security Alert - HayagSync',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.security.alert-mail',
        );
    }

    public function attachments(): array
    {
        return [];
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            logger()->error('SecurityAlertMail failed.', [
                'security_event_id' => $this->securityEvent->id ?? null,
                'admin_id' => $this->admin->id ?? null,
                'admin_email' => $this->admin->email ?? null,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }
}
