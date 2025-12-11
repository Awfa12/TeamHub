<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelAccessTest extends TestCase
{
    use RefreshDatabase;

    /** Private channel is hidden from non-members, visible to members. */
    public function test_private_channel_hidden_from_non_member(): void
    {
        $team = Team::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();

        $team->users()->attach($owner, ['role' => 'owner']);
        $team->users()->attach($member, ['role' => 'member']);

        $private = Channel::factory()->create([
            'team_id' => $team->id,
            'is_private' => true,
        ]);
        $private->users()->attach($member, ['role' => 'participant']);

        // Outsider should be forbidden
        $this->actingAs($outsider)
            ->get(route('channels.index', $team))
            ->assertForbidden();

        // Member should see it
        $this->actingAs($member)
            ->get(route('channels.index', $team))
            ->assertStatus(200)
            ->assertSee($private->name);
    }

    /** Archived channel hidden by default, visible when show_archived=1. */
    public function test_archived_hidden_by_default(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $team->users()->attach($user, ['role' => 'member']);

        $archived = Channel::factory()->create([
            'team_id' => $team->id,
            'archived' => true,
        ]);

        $this->actingAs($user)
            ->get(route('channels.index', $team))
            ->assertStatus(200)
            ->assertDontSee($archived->name);

        $this->actingAs($user)
            ->get(route('channels.index', [$team, 'show_archived' => 1]))
            ->assertStatus(200)
            ->assertSee($archived->name);
    }

    /** Archived channel page shows read-only banner. */
    public function test_archived_channel_read_only_banner(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $team->users()->attach($user, ['role' => 'member']);

        $archived = Channel::factory()->create([
            'team_id' => $team->id,
            'archived' => true,
        ]);

        $this->actingAs($user)
            ->get(route('channels.show', [$team, $archived]))
            ->assertStatus(200)
            ->assertSee('Archived (read-only)');
    }
}
