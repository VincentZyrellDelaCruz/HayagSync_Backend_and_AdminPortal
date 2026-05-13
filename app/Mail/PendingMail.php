<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PendingMail extends Mailable
{
    use Queueable, SerializesModels;

    protected string $lastname;
    protected string $password;
    protected string $status;
    protected string $gender;

    /**
     * Create a new message instance.
     */
    public function __construct(string $lastname, string $password, string $status, string $gender)
    {
        $this->lastname = $lastname;
        $this->password = $password ?? '';
        $this->status = $status;
        $this->gender = $gender;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->status === 'Approved' ? 'Registration Approved' : 'Registration Rejected',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.pending_mail',
            with: [
                'lastname' => $this->lastname,
                'password' => $this->password,
                'status' => $this->status,
                'gender' => $this->gender,
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
}
