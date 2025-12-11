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
use App\Events\ReactionToggled;
use App\Models\Reaction;
use App\Events\ReadReceiptUpdated;
use App\Models\MessageRead;
use App\Jobs\SendMessageNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
    public string $searchTerm = '';
    public $searchResults = [];
    public bool $notificationEmailsEnabled = true;
    public bool $channelArchived = false;
    
    // Edit mode properties
    public ?int $editingMessageId = null;
    public string $editBody = '';
    
    // Reply mode properties
    public ?int $replyingToMessageId = null;
    
    // Track which threads are expanded (using string keys for better Livewire serialization)
    public array $expandedThreads = [];
    public ?int $lastReadMessageId = null;

    public function mount(Team $team, Channel $channel)
    {
        $this->team = $team;
        $this->channel = $channel;
        $this->teamId = $team->id;
        $this->channelId = $channel->id;
        $this->notificationEmailsEnabled = (bool) (auth()->user()->notification_emails ?? true);
        $this->channelArchived = (bool) $channel->archived;
        // Only load parent messages (not replies) - lazy load replies when expanded
        $this->chatMessages = Message::with(['user', 'reactions.user', 'reads.user'])
                        ->withCount('replies')
                        ->where('channel_id', $channel->id)
                        ->whereNull('parent_id')
                        ->latest()->take(30)->get()->reverse()->values();

        $this->markLatestRead();
    }

    public function sendMessage()
    {
        if ($this->channelArchived) {
            $this->addError('body', 'Channel is archived (read-only).');
            return;
        }
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

            // Email notify parent author if enabled and not self
            $parent = Message::with('user')->find($this->replyingToMessageId);
            if ($parent && $parent->user_id !== $userId && ($parent->user->notification_emails ?? false)) {
                SendMessageNotification::dispatch($message->id, $parent->user_id);
            }
        } else {
            // Add to main messages
            $message->replies_count = 0;
            $message->setRelation('reactions', collect());
            $this->chatMessages->push($message->load('user'));
        }

        // Mention email notifications (@username) to team members
        if (! empty($this->body)) {
            preg_match_all('/@([\\p{L}\\p{N}_\\.\\-]+)/u', $this->body, $matches);
            $mentionTokens = collect($matches[1] ?? [])->map(fn($n) => mb_strtolower($n))->unique()->values();

            if ($mentionTokens->isNotEmpty()) {
                $teamUsers = $this->team->users; // lazy-load once
                $mentionedUsers = $teamUsers->filter(function ($user) use ($mentionTokens) {
                    $name = mb_strtolower($user->name ?? '');
                    $emailLocal = mb_strtolower(strtok($user->email ?? '', '@'));
                    return $mentionTokens->contains($name) || ($emailLocal && $mentionTokens->contains($emailLocal));
                });

                foreach ($mentionedUsers as $mentioned) {
                    if ($mentioned->id !== $userId && ($mentioned->notification_emails ?? false)) {
                        SendMessageNotification::dispatch($message->id, $mentioned->id);
                    }
                }
            }
        }

        // Clear the input and scroll to bottom
        $this->reset(['body', 'file', 'replyingToMessageId']);
        $this->dispatch('message-sent');
        // Mark as read for sender
        $this->markLatestRead();
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
            $message->setRelation('reactions', collect());
            $this->chatMessages->push($message);
        }
        
        // Notify frontend to scroll to bottom
        $this->dispatch('message-received');

        // Mark latest as read
        $this->markLatestRead();
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
            // Remove from array properly for Livewire reactivity
            $expanded = $this->expandedThreads;
            unset($expanded[$messageId]);
            $this->expandedThreads = $expanded;
        } else {
            $this->expandedThreads[$messageId] = true;
            // Always load replies when expanding (ensures fresh data)
            $this->chatMessages = $this->chatMessages->map(function ($msg) use ($messageId) {
                if ($msg->id === $messageId) {
                    $msg->load(['replies.user', 'replies.reactions.user', 'replies.reads.user']);
                }
                return $msg;
            });
        }
    }

    private function markLatestRead(): void
    {
        $userId = auth()->id();
        if (!$userId) {
            return;
        }

        $latest = Message::where('channel_id', $this->channelId)
            ->latest('id')
            ->first();

        if (!$latest || $this->lastReadMessageId === $latest->id) {
            return;
        }

        MessageRead::updateOrCreate(
            [
                'message_id' => $latest->id,
                'user_id' => $userId,
            ],
            ['read_at' => now()]
        );

        $this->lastReadMessageId = $latest->id;

        ReadReceiptUpdated::dispatch(
            $latest->id,
            $this->channelId,
            $userId,
            auth()->user()->name
        );

        // Update local collection for latest message
        $this->chatMessages = $this->chatMessages->map(function ($msg) use ($latest) {
            if ($msg->id === $latest->id) {
                $msg->load('reads.user');
            }
            return $msg;
        });
    }

    // Common emojis for quick reactions
    public array $quickEmojis = ['👍', '❤️', '😂', '😮', '😢', '🎉'];

    public function toggleReaction(int $messageId, string $emoji): void
    {
        $userId = auth()->id();
        if (!$userId) {
            return;
        }

        $existingReaction = Reaction::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->where('emoji', $emoji)
            ->first();

        $message = Message::find($messageId);
        if (!$message) {
            return;
        }

        if ($existingReaction) {
            // Remove reaction
            $existingReaction->delete();
            $action = 'removed';
        } else {
            // Add reaction
            Reaction::create([
                'message_id' => $messageId,
                'user_id' => $userId,
                'emoji' => $emoji,
            ]);
            $action = 'added';
        }

        // Broadcast the reaction change
        ReactionToggled::dispatch(
            $messageId,
            $this->channelId,
            $emoji,
            $action,
            $userId,
            auth()->user()->name
        );

        // Refresh reactions for this message
        $this->refreshMessageReactions($messageId);
    }

    public function reactionToggledReceived(array $payload): void
    {
        // Skip if it's our own reaction (already updated locally)
        if ($payload['user_id'] === auth()->id()) {
            return;
        }

        $this->refreshMessageReactions($payload['message_id']);
    }

    public function readReceiptReceived(array $payload): void
    {
        // Skip if it's our own read (already updated locally)
        if ($payload['user_id'] === auth()->id()) {
            return;
        }

        $this->refreshMessageReads($payload['message_id']);
    }

    public function searchMessages(): void
    {
        $term = trim($this->searchTerm);

        if (strlen($term) < 2) {
            $this->searchResults = [];
            return;
        }

        $this->searchResults = Message::with(['user', 'parent.user'])
            ->where('channel_id', $this->channelId)
            ->whereNotNull('body')
            ->where('body', 'like', '%' . $term . '%')
            ->latest('id')
            ->take(20)
            ->get();
    }

    public function updatedSearchTerm($value): void
    {
        $term = trim($value);
        if (strlen($term) >= 2) {
            $this->searchMessages();
        } else {
            $this->searchResults = [];
        }
    }

    public function clearSearch(): void
    {
        $this->searchTerm = '';
        $this->searchResults = [];
    }

    public function toggleNotificationEmails(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $user->notification_emails = ! $user->notification_emails;
        $user->save();
        $this->notificationEmailsEnabled = (bool) $user->notification_emails;
    }

    public function jumpToMessage(int $messageId, ?int $parentId = null): void
    {
        // If it's a reply, ensure the parent thread is expanded and replies loaded
        if ($parentId) {
            $this->expandedThreads[$parentId] = true;
            $this->chatMessages = $this->chatMessages->map(function ($msg) use ($parentId) {
                if ($msg->id === $parentId) {
                    $msg->load(['replies.user', 'replies.reactions.user', 'replies.reads.user']);
                }
                return $msg;
            });

            $targetId = "reply-{$messageId}";
        } else {
            $targetId = "msg-{$messageId}";
        }

        // Dispatch to frontend to scroll
        $this->dispatch('scroll-to-message', id: $targetId);
    }

    private function refreshMessageReactions(int $messageId): void
    {
        $message = Message::with('reactions.user')->find($messageId);
        if (!$message) {
            return;
        }
        
        // If it's a reply, update the reaction in the parent's replies collection
        if ($message->parent_id) {
            $this->chatMessages = $this->chatMessages->map(function ($msg) use ($message) {
                if ($msg->id === $message->parent_id && $msg->relationLoaded('replies')) {
                    // Update just the specific reply's reactions
                    $msg->replies = $msg->replies->map(function ($reply) use ($message) {
                        if ($reply->id === $message->id) {
                            $reply->setRelation('reactions', $message->reactions);
                        }
                        return $reply;
                    });
                }
                return $msg;
            });
        } else {
            // It's a parent message - just update its reactions
            $this->chatMessages = $this->chatMessages->map(function ($msg) use ($message, $messageId) {
                if ($msg->id === $messageId) {
                    $msg->setRelation('reactions', $message->reactions);
                }
                return $msg;
            });
        }
    }

    private function refreshMessageReads(int $messageId): void
    {
        $message = Message::with('reads.user')->find($messageId);
        if (!$message) {
            return;
        }

        // If it's a reply, refresh parent's replies
        if ($message->parent_id) {
            $this->chatMessages = $this->chatMessages->map(function ($msg) use ($message) {
                if ($msg->id === $message->parent_id && $msg->relationLoaded('replies')) {
                    $msg->replies = $msg->replies->map(function ($reply) use ($message) {
                        if ($reply->id === $message->id) {
                            $reply->setRelation('reads', $message->reads);
                        }
                        return $reply;
                    });
                }
                return $msg;
            });
        } else {
            // Direct message
            $this->chatMessages = $this->chatMessages->map(function ($msg) use ($messageId, $message) {
                if ($msg->id === $messageId) {
                    $msg->setRelation('reads', $message->reads);
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
