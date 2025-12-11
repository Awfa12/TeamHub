<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReactionToggled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $messageId;
    public int $channelId;
    public string $emoji;
    public string $action; // 'added' or 'removed'
    public int $userId;
    public string $userName;
    
    /**
     * Create a new event instance.
     */
    public function __construct(
        int $messageId, 
        int $channelId, 
        string $emoji, 
        string $action,
        int $userId,
        string $userName
    ) {
        $this->messageId = $messageId;
        $this->channelId = $channelId;
        $this->emoji = $emoji;
        $this->action = $action;
        $this->userId = $userId;
        $this->userName = $userName;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('channel.' . $this->channelId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'reaction.toggled';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'emoji' => $this->emoji,
            'action' => $this->action,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
        ];
    }
}

