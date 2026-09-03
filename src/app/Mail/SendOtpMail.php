<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendOtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    /**
     * Create a new message instance.
     */
    public function __construct(public string $otp) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'OTP Verification',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.security.otp-mail',
            with: [
                'otp' => $this->otp,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
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
