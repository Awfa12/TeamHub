<?php

use App\Models\Channel;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Presence Channel for Real-Time Chat
|--------------------------------------------------------------------------
|
| Presence channels allow us to:
| 1. Know who's currently viewing the channel
| 2. Use whispers for typing indicators (client-to-client, no server)
| 3. Show online/offline status
|
| Return user data array if authorized, false if not.
|
*/

Broadcast::channel('channel.{channel}', function ($user, Channel $channel) {
    // Must belong to the team
    if (! $user->teams()->whereKey($channel->team_id)->exists()) {
        return false;
    }

    // Check private channel membership
    if ($channel->is_private && ! $channel->users()->whereKey($user->id)->exists()) {
        return false;
    }

    // Return user data for presence channel
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});

