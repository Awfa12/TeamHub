<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        // Demo users with different roles
        $owner = User::factory()->create([
            'name' => 'Demo Owner',
            'email' => 'owner@example.com',
            // password = "password" from factory
        ]);

        $admin = User::factory()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@example.com',
        ]);

        $member = User::factory()->create([
            'name' => 'Demo Member',
            'email' => 'member@example.com',
        ]);

        // Create a team
        $team = Team::create([
            'name' => 'Demo Team',
            'slug' => 'demo-team',
            'owner_id' => $owner->id,
            'settings' => [],
            'active' => true,
        ]);

        // Attach owner/admin/member in pivot
        $owner->teams()->attach($team->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);
        $admin->teams()->attach($team->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);
        $member->teams()->attach($team->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        // Create channels: one public, one private
        $general = Channel::create([
            'team_id' => $team->id,
            'name' => 'general',
            'slug' => 'general',
            'description' => 'General discussion',
            'is_private' => false,
            'creator_id' => $owner->id,
            'archived' => false,
        ]);

        $private = Channel::create([
            'team_id' => $team->id,
            'name' => 'leadership',
            'slug' => 'leadership',
            'description' => 'Private leadership channel',
            'is_private' => true,
            'creator_id' => $owner->id,
            'archived' => false,
        ]);

        // Attach owner/admin to private channel
        $private->users()->attach($owner->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);
        $private->users()->attach($admin->id, [
            'role' => 'participant',
            'joined_at' => now(),
        ]);
    }
}

