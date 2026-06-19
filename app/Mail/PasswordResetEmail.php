<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetEmail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $token;
    public string $frontendUrl;

    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
        // 🔥 URL du frontend pour la réinitialisation
        $this->frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Réinitialisation de votre mot de passe',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: [
                'user' => $this->user,
                'token' => $this->token,
                'frontendUrl' => $this->frontendUrl,
            ]
        );
    }
}