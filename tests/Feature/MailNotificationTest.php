<?php

namespace Tests\Feature;

use App\Mail\MessageReplyNotification;
use App\Models\Channel;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** Replies and @mentions queue mails for recipients with alerts on. */
    public function test_reply_and_mention_queue_mails(): void
    {
        Mail::fake();

        $team = Team::factory()->create();
        $alice = User::factory()->create(['name' => 'Alice', 'notification_emails' => true]);
        $bob = User::factory()->create(['name' => 'Bob', 'notification_emails' => true]);
        $team->users()->attach($alice, ['role' => 'member']);
        $team->users()->attach($bob, ['role' => 'member']);

        config(['queue.default' => 'sync']);

        $channel = Channel::factory()->create(['team_id' => $team->id]);
        $parent = Message::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $alice->id,
            'body' => 'Parent message',
        ]);

        Session::start();
        $token = Session::token();

        // Bob replies -> Alice should get mail
        $this->actingAs($bob)->post(route('channels.messages.store', [$team, $channel]), [
            'body' => 'Replying to you',
            'parent_id' => $parent->id,
            '_token' => $token,
        ])->assertRedirect();

        Mail::assertSent(MessageReplyNotification::class, function ($mail) use ($alice, $parent) {
            return $mail->hasTo($alice->email) && $mail->message->parent_id === $parent->id;
        });

        // Bob mentions Alice in new message -> Alice should get mail
        $this->actingAs($bob)->post(route('channels.messages.store', [$team, $channel]), [
            'body' => 'Hello @Alice',
            '_token' => $token,
        ])->assertRedirect();

        Mail::assertSent(MessageReplyNotification::class, function ($mail) use ($alice) {
            return $mail->hasTo($alice->email);
        });
    }
}
