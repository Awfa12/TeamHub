<?php

namespace App\Livewire;

use App\Models\Team;
use App\Models\Channel;
use App\Models\Message;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Events\MessageDeleted;

class ChannelChat extends Component
{
    use AuthorizesRequests;

    public Team $team; 
    public Channel $channel; 
    public int $teamId;
    public int $channelId;
    public $body = ''; 
    public $chatMessages;
    
    // Edit mode properties
    public ?int $editingMessageId = null;
    public string $editBody = '';

    public function mount(Team $team, Channel $channel)
    {
        $this->team = $team;
        $this->channel = $channel;
        $this->teamId = $team->id;
        $this->channelId = $channel->id;
        $this->chatMessages = Message::with('user')
                        ->where('channel_id', $channel->id)
                        ->latest()->take(30)->get()->reverse()->values();
    }

    public function sendMessage()
    {
        // Authorize user to view team and channel
        $team = Team::findOrFail($this->teamId);
        $channel = Channel::findOrFail($this->channelId);

        $this->authorize('view', $team);
        $this->authorize('view', $channel);

        // Validate body
        $validated = $this->validate([
            'body' => 'required|string|max:500',
        ]);

        $userId = auth()->id();
        if (! $userId) {
            abort(401);
        }

        $message = Message::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $userId,
            'channel_id' => $channel->id,
            'body' => $validated['body'],
        ]);

        MessageSent::dispatch($message);

        $this->chatMessages->push($message->load('user'));

        // Clear the input and scroll to bottom
        $this->reset('body');
        $this->dispatch('message-sent');
    }

    public function messageReceived(array $payload): void
    {
        // avoid duplicates
        if ($this->chatMessages->firstWhere('uuid', $payload['uuid'])) {
            return;
        }

        // Fetch the actual Message model to maintain collection type consistency
        $message = Message::with('user')->find($payload['id']);
        
        if ($message) {
            $this->chatMessages->push($message);
            // Notify frontend to scroll to bottom
            $this->dispatch('message-received');
        }
    }

    public function startEditing(int $messageId): void
    {
        $message = $this->chatMessages->firstWhere('id', $messageId);
        
        if (!$message || $message->user_id !== auth()->id()) {
            return;
        }
        
        $this->editingMessageId = $messageId;
        $this->editBody = $message->body;
    }

    public function cancelEditing(): void
    {
        $this->editingMessageId = null;
        $this->editBody = '';
    }

    public function updateMessage(): void
    {
        if (!$this->editingMessageId) {
            return;
        }

        $message = Message::find($this->editingMessageId);
        
        if (!$message || $message->user_id !== auth()->id()) {
            $this->cancelEditing();
            return;
        }

        $validated = $this->validate([
            'editBody' => 'required|string|max:500',
        ]);

        $message->update([
            'body' => $validated['editBody'],
            'edited_at' => now(),
        ]);

        // Dispatch broadcast event
        MessageUpdated::dispatch($message->fresh());

        // Update local collection
        $this->chatMessages = $this->chatMessages->map(function ($msg) use ($message) {
            if ($msg->id === $message->id) {
                return $message->fresh()->load('user');
            }
            return $msg;
        });

        $this->cancelEditing();
    }

    public function messageUpdatedReceived(array $payload): void
    {
        $this->chatMessages = $this->chatMessages->map(function ($msg) use ($payload) {
            if ($msg->id === $payload['id']) {
                // Fetch fresh message from DB
                return Message::with('user')->find($payload['id']) ?? $msg;
            }
            return $msg;
        });
    }

    public function deleteMessage(int $messageId): void
    {
        $message = Message::find($messageId);
        
        if (!$message || $message->user_id !== auth()->id()) {
            return;
        }

        $channelId = $message->channel_id;
        
        // Soft delete the message
        $message->delete();

        // Dispatch broadcast event
        MessageDeleted::dispatch($messageId, $channelId);

        // Mark as deleted in local collection (keep for UI placeholder)
        $this->chatMessages = $this->chatMessages->map(function ($msg) use ($messageId) {
            if ($msg->id === $messageId) {
                $msg->deleted_at = now();
            }
            return $msg;
        });
    }

    public function messageDeletedReceived(array $payload): void
    {
        $this->chatMessages = $this->chatMessages->map(function ($msg) use ($payload) {
            if ($msg->id === $payload['id']) {
                $msg->deleted_at = now();
            }
            return $msg;
        });
    }

    public function render()
    {
        return view('livewire.channel-chat');
    }
}
