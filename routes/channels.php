<?php

use App\Models\Channel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('channel.{channel}', function ($user, Channel $channel) {
    // Must belong to the team
    if (! $user->teams()->whereKey($channel->team_id)->exists()) {
        return false;
    }

    // Public channel is fine
    if (! $channel->is_private) {
        return true;
    }

    // Private: must be a member of the channel
    return $channel->users()->whereKey($user->id)->exists();
});

