<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $subjectText;
    public string $messageText;
    public string $type;

    public function __construct(User $user, string $subject, string $message, string $type = 'notification')
    {
        $this->user = $user;
        $this->subjectText = $subject;
        $this->messageText = $message;
        $this->type = $type;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectText,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
        );
    }
}