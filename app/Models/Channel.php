<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use HasFactory;
    protected $fillable = [
        'team_id',
        'name',
        'slug',
        'description',
        'is_private',
        'creator_id',
        'archived',
    ];

    protected $casts = ['is_private' => 'bool', 'archived' => 'bool'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'channel_user')->withPivot('role', 'joined_at');
    }
}
