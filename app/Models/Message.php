<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    /** @use HasFactory<\Database\Factories\MessageFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'channel_id',
        'parent_id',
        'user_id',
        'body',
        'file_name',
        'file_path',
        'file_size',
        'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent message (for thread replies)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    /**
     * Get replies to this message
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_id')->with(['user', 'reactions.user'])->orderBy('created_at');
    }

    /**
     * Get reactions to this message
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * Get grouped reactions with counts and user info
     */
    public function getGroupedReactionsAttribute(): Collection
    {
        return $this->reactions
            ->groupBy('emoji')
            ->map(function ($reactions, $emoji) {
                return [
                    'emoji' => $emoji,
                    'count' => $reactions->count(),
                    'users' => $reactions->pluck('user.name')->filter()->toArray(),
                    'user_ids' => $reactions->pluck('user_id')->toArray(),
                    'reacted_by_me' => $reactions->contains('user_id', auth()->id()),
                ];
            })
            ->values();
    }

    /**
     * Get the count of replies
     */
    public function getRepliesCountAttribute(): int
    {
        return $this->replies()->count();
    }

    /**
     * Check if this is a thread reply
     */
    public function getIsReplyAttribute(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Get the file URL for downloading/viewing
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return route('files.show', $this);
    }

    /**
     * Get the download URL
     */
    public function getDownloadUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return route('files.download', $this);
    }

    /**
     * Check if the file is an image
     */
    public function getIsImageAttribute(): bool
    {
        if (!$this->file_name) {
            return false;
        }

        $extension = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
    }

    /**
     * Format file size for display
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 1) . ' ' . $units[$index];
    }
}
