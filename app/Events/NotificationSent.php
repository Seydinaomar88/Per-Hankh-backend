<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Notification $notification;
    public int $userId;

    public function __construct(Notification $notification, int $userId)
    {
        $this->notification = $notification;
        $this->userId = $userId;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'notification.new';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'message' => $this->notification->message,
            'type' => $this->notification->type,
            'data' => json_decode($this->notification->data, true),
            'is_read' => $this->notification->is_read,
            'created_at' => $this->notification->created_at->toISOString(),
        ];
    }
}