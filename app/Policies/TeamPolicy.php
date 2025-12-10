<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // any authenticated user can list their teams
    }

    public function view(User $user, Team $team): bool
    {
        return $this->isMember($user, $team);
    }

    public function create(User $user): bool
    {
        return true; // any authenticated user can create a new team
    }

    public function update(User $user, Team $team): bool
    {
        return $this->hasRole($user, $team, ['owner', 'admin']);
    }

    public function delete(User $user, Team $team): bool
    {
        return $this->hasRole($user, $team, ['owner']);
    }

    public function manageMembers(User $user, Team $team): bool
    {
        return $this->hasRole($user, $team, ['owner', 'admin']);
    }

    protected function isMember(User $user, Team $team): bool
    {
        return $user->teams()->where('team_id', $team->id)->exists();
    }

    protected function hasRole(User $user, Team $team, array $roles): bool
    {
        $membership = $user->teams()
            ->where('team_id', $team->id)
            ->first();

        if (! $membership) {
            return false;
        }

        return in_array($membership->pivot->role, $roles, true);
    }
}

