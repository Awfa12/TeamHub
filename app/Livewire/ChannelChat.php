<?php

namespace App\Livewire;

use App\Models\Team;
use App\Models\Channel;
use App\Models\Message;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Events\MessageDeleted;

class ChannelChat extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public Team $team; 
    public Channel $channel; 
    public int $teamId;
    public int $channelId;
    public $body = ''; 
    public $chatMessages;
    public $file;
    
    // Edit mode properties
    public ?int $editingMessageId = null;
    public string $editBody = '';
    
    // Reply mode properties
    public ?int $replyingToMessageId = null;
    public array $expandedThreads = [];

    public function mount(Team $team, Channel $channel)
    {
        $this->team = $team;
        $this->channel = $channel;
        $this->teamId = $team->id;
        $this->channelId = $channel->id;
        // Only load parent messages (not replies) - lazy load replies when expanded
        $this->chatMessages = Message::with('user')
                        ->withCount('replies')
                        ->where('channel_id', $channel->id)
                        ->whereNull('parent_id')
                        ->latest()->take(30)->get()->reverse()->values();
    }

    public function sendMessage()
    {
        // Authorize user to view team and channel
        $team = Team::findOrFail($this->teamId);
        $channel = Channel::findOrFail($this->channelId);

        $this->authorize('view', $team);
        $this->authorize('view', $channel);

        // Validate - require body OR file
        $this->validate([
            'body' => 'nullable|string|max:500',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        // Must have body or file
        if (empty($this->body) && !$this->file) {
            $this->addError('body', 'Please enter a message or attach a file.');
            return;
        }

        $userId = auth()->id();
        if (! $userId) {
            abort(401);
        }

        // Handle file upload
        $fileData = [];
        if ($this->file) {
            $path = $this->file->store('messages/' . $channel->id, 'minio');
            $fileData = [
                'file_name' => $this->file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $this->file->getSize(),
            ];
        }

        $message = Message::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'user_id' => $userId,
            'channel_id' => $channel->id,
            'parent_id' => $this->replyingToMessageId,
            'body' => $this->body ?: null,
        ], $fileData));

        MessageSent::dispatch($message);

        // If it's a reply, add to parent's replies and expand thread
        if ($this->replyingToMessageId) {
            $this->chatMessages = $this->chatMessages->map(function ($msg) use ($message) {
                if ($msg->id === $this->replyingToMessageId) {
                    $msg->load('replies.user');
                    $msg->replies_count = $msg->replies->count();
                }
                return $msg;
            });
            $this->expandedThreads[$this->replyingToMessageId] = true;
        } else {
            // Add to main messages
            $message->replies_count = 0;
            $this->chatMessages->push($message->load('user'));
        }

        // Clear the input and scroll to bottom
        $this->reset(['body', 'file', 'replyingToMessageId']);
        $this->dispatch('message-sent');
    }

    public function messageReceived(array $payload): void
    {
        // Fetch the actual Message model to maintain collection type consistency
        $message = Message::with('user')->find($payload['id']);
        
        if (!$message) {
            return;
        }

        // If it's a reply, update parent's reply count and load replies if expanded
        if ($message->parent_id) {
            $this->chatMessages = $this->chatMessages->map(function ($msg) use ($message) {
                if ($msg->id === $message->parent_id) {
                    $msg->replies_count = ($msg->replies_count ?? 0) + 1;
                    // Only load replies if thread is expanded
                    if (isset($this->expandedThreads[$msg->id])) {
                        $msg->load('replies.user');
                    }
                }
                return $msg;
            });
        } else {
            // avoid duplicates for main messages
            if ($this->chatMessages->firstWhere('uuid', $payload['uuid'])) {
                return;
            }
            $message->replies_count = 0;
            $this->chatMessages->push($message);
        }
        
        // Notify frontend to scroll to bottom
        $this->dispatch('message-received');
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

    public function startReply(int $messageId): void
    {
        $this->replyingToMessageId = $messageId;
        $this->expandedThreads[$messageId] = true;
        $this->dispatch('focus-message-input');
    }

    public function startReplyToReply(int $parentId, int $replyUserId, string $replyUserName): void
    {
        $this->replyingToMessageId = $parentId;
        $this->expandedThreads[$parentId] = true;
        // Pre-fill with @mention if replying to someone else
        if ($replyUserId !== auth()->id()) {
            $this->body = "@{$replyUserName} ";
        }
        $this->dispatch('focus-message-input');
    }

    public function cancelReply(): void
    {
        $this->replyingToMessageId = null;
        $this->body = '';
    }

    public function toggleThread(int $messageId): void
    {
        if (isset($this->expandedThreads[$messageId])) {
            unset($this->expandedThreads[$messageId]);
        } else {
            $this->expandedThreads[$messageId] = true;
            // Load replies for this message (lazy loading)
            $this->chatMessages = $this->chatMessages->map(function ($msg) use ($messageId) {
                if ($msg->id === $messageId && !$msg->relationLoaded('replies')) {
                    $msg->load('replies.user');
                }
                return $msg;
            });
        }
    }

    public function render()
    {
        return view('livewire.channel-chat');
    }
}
