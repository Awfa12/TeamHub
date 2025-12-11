<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Channel;
use App\Models\Team;
use Illuminate\Http\Request;
use App\Events\MessageSent;
use App\Jobs\SendMessageNotification;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MessageController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Team $team, Channel $channel)
    {
        $this->authorize('view', $team);
        $this->authorize('view', $channel);
        $user = $request->user();

        if ($channel->archived) {
            abort(403, 'Channel is archived.');
        }

        $validated = $request->validate([
            'body' => 'required|string|max:500',
            'parent_id' => 'nullable|exists:messages,id',
        ]);

        $message = Message::create([
            'uuid' => (string) Str::uuid(),
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'body' => $validated['body'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        MessageSent::dispatch($message);

        // Email notify parent author (reply)
        if (! empty($message->parent_id)) {
            $parent = Message::with('user')->find($message->parent_id);
            if ($parent && $parent->user_id !== $user->id && ($parent->user->notification_emails ?? false)) {
                SendMessageNotification::dispatch($message->id, $parent->user_id);
            }
        }

        // @mention notifications
        if (! empty($message->body)) {
            preg_match_all('/@([\\p{L}\\p{N}_\\.\\-]+)/u', $message->body, $matches);
            $mentionTokens = collect($matches[1] ?? [])->map(fn ($n) => mb_strtolower($n))->unique()->values();

            if ($mentionTokens->isNotEmpty()) {
                $teamUsers = $team->users()->get();

                foreach ($teamUsers as $mentioned) {
                    $mentionedNameLower = mb_strtolower($mentioned->name);
                    $mentionedEmailLocalPartLower = mb_strtolower(explode('@', $mentioned->email)[0]);

                    if ($mentioned->id !== $user->id && ($mentioned->notification_emails ?? false) &&
                        ($mentionTokens->contains($mentionedNameLower) || $mentionTokens->contains($mentionedEmailLocalPartLower))) {
                        SendMessageNotification::dispatch($message->id, $mentioned->id);
                    }
                }
            }
        }

        return back()->with('status', 'Message sent.');
    }
}
