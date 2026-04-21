<?php

namespace App\Mail;

use App\Models\Incident;
use App\Models\IncidentStatus;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IncidentMail extends Mailable
{
    use Queueable, SerializesModels;

    public Incident $incident;
    public String $status;
    public String $recipient;
    public User $sender;
    public String $type;
    public String $note;
    // public String $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(Incident $incident, String $recipient,
        String $status, User $sender, String $note, String $type='Parent')
    {
        $this->incident = $incident;
        $this->recipient = $recipient;
        $this->status = $status;
        $this->sender = $sender;
        $this->type = $type;
        $this->note = $note;
        // $this->reason = $reason ?? '';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Incident Notification',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.incident_mail',
            with: [
                'incident' => $this->incident,
                'recipient' => $this->recipient,
                'status' => $this->status,
                'sender' => $this->sender,
                'type' => $this->type,
                // 'reason' => $this->reason,
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
