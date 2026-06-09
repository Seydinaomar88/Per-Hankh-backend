<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserPresence implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $user;
    public int $workspaceId;
    public string $status;

    public function __construct(array $user, int $workspaceId, string $status)
    {
        $this->user = $user;
        $this->workspaceId = $workspaceId;
        $this->status = $status;
    }

    public function broadcastOn(): Channel
    {
        return new Channel("presence.{$this->workspaceId}");
    }

    public function broadcastAs(): string
    {
        return "user.presence";
    }

    public function broadcastWith(): array
    {
        return [
            "user_id" => $this->user["id"],
            "name" => $this->user["name"],
            "username" => $this->user["username"],
            "status" => $this->status,
            "timestamp" => now()->toIso8601String()
        ];
    }
}
