<?php

namespace App\Events;

use App\Models\TaskNoteComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskNoteCommentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TaskNoteComment $comment;
    public int $taskId;
    public int $noteId;
    public array $mentions;

    public function __construct(TaskNoteComment $comment, int $taskId, int $noteId, array $mentions = [])
    {
        $this->comment = $comment;
        $this->taskId = $taskId;
        $this->noteId = $noteId;
        $this->mentions = $mentions;
    }

    // Canal PUBLIC (pas besoin d'authentification)
    public function broadcastOn(): Channel
    {
        return new Channel('task-note-' . $this->taskId . '-' . $this->noteId);
    }

    public function broadcastAs(): string
    {
        return 'task.note.comment.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->comment->id,
            'task_note_id' => $this->noteId,
            'task_id' => $this->taskId,
            'user_id' => $this->comment->user_id,
            'user_name' => $this->comment->user->name ?? 'Utilisateur',
            'content' => $this->comment->content,
            'mentions' => $this->mentions,
            'created_at' => $this->comment->created_at?->toISOString(),
        ];
    }
}