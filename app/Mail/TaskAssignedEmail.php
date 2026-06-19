<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssignedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Task $task;
    public string $assignedBy;
    public string $frontendUrl;

    public function __construct(User $user, Task $task, string $assignedBy)
    {
        $this->user = $user;
        $this->task = $task;
        $this->assignedBy = $assignedBy;
        // 🔥 URL du frontend
        $this->frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle tâche assignée 📋',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.task-assigned',
            with: [
                'user' => $this->user,
                'task' => $this->task,
                'assignedBy' => $this->assignedBy,
                'frontendUrl' => $this->frontendUrl,
            ]
        );
    }
}