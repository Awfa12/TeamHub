<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class MessageFlowTest extends TestCase
{
    use RefreshDatabase;

    /** User can post and replies show, archived blocks send. */
    public function test_message_flow_and_archive_block(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $team = Team::factory()->create();
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);
        $team->users()->attach($alice, ['role' => 'member']);
        $team->users()->attach($bob, ['role' => 'member']);

        $channel = Channel::factory()->create(['team_id' => $team->id]);
        // ensure channel membership for both (even though channel is public)
        $channel->users()->attach($alice, ['role' => 'participant']);
        $channel->users()->attach($bob, ['role' => 'participant']);

        Session::start();
        $token = Session::token();

        $this->assertTrue($alice->teams()->where('team_id', $team->id)->exists());
        $this->assertTrue($bob->teams()->where('team_id', $team->id)->exists());
        $this->assertTrue($channel->users()->whereKey($alice->id)->exists());

        // Alice posts
        $this->actingAs($alice)->post(route('channels.messages.store', [$team, $channel]), [
            'body' => 'Hello world',
            '_token' => $token,
        ])->assertRedirect();

        $msg = Message::first();
        $this->assertEquals('Hello world', $msg->body);

        // Bob replies
        $this->actingAs($bob)->post(route('channels.messages.store', [$team, $channel]), [
            'body' => 'Reply here',
            'parent_id' => $msg->id,
            '_token' => $token,
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', ['body' => 'Reply here', 'parent_id' => $msg->id]);

        // Archive channel: sending now blocked
        $channel->update(['archived' => true]);

        $this->actingAs($alice)->post(route('channels.messages.store', [$team, $channel]), [
            'body' => 'Should be blocked',
            '_token' => $token,
        ])->assertForbidden();
    }
}
