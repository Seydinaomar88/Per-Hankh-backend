<?php

namespace App\Events;

use App\Models\Task;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskMoved implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Task $task;

    /**
     * Create a new event instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Broadcast channel
     */
    public function broadcastOn(): array
    {
        return [

            new PrivateChannel(
                'workspace.' . $this->task->workspace_id
            )

        ];
    }

    /**
     * Event name
     */
    public function broadcastAs(): string
    {
        return 'task.moved';
    }
}