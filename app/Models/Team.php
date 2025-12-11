<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'owner_id',
        'settings',
        'active',
    ];

    protected $casts = ['settings' => 'array', 'active' => 'bool'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')->withPivot('role', 'joined_at', 'last_seen_at');
    }   

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }
}
