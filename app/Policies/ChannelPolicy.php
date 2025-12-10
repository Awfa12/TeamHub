<?php

namespace App\Policies;

use App\Models\Channel;
use App\Models\User;

class ChannelPolicy
{
    public function view(User $user, Channel $channel): bool
    {
        $team = $channel->team;

        if (! $this->isTeamMember($user, $team->id)) {
            return false;
        }

        if (! $channel->is_private) {
            return true;
        }

        return $channel->users()->whereKey($user->id)->exists();
    }

    public function create(User $user, mixed $channelOrTeam): bool
    {
        $teamId = $channelOrTeam instanceof Channel
            ? $channelOrTeam->team_id
            : ($channelOrTeam->id ?? null);

        if (! $teamId) {
            return false;
        }

        return $this->hasTeamRole($user, $teamId, ['owner', 'admin']);
    }

    public function update(User $user, Channel $channel): bool
    {
        return $this->hasTeamRole($user, $channel->team_id, ['owner', 'admin']);
    }

    public function delete(User $user, Channel $channel): bool
    {
        return $this->hasTeamRole($user, $channel->team_id, ['owner']);
    }

    protected function isTeamMember(User $user, int $teamId): bool
    {
        return $user->teams()->where('team_id', $teamId)->exists();
    }

    protected function hasTeamRole(User $user, int $teamId, array $roles): bool
    {
        $membership = $user->teams()
            ->where('team_id', $teamId)
            ->first();

        if (! $membership) {
            return false;
        }

        return in_array($membership->pivot->role, $roles, true);
    }
}

