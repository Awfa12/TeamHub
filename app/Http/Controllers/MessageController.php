<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Channel;
use App\Models\Team;
use Illuminate\Http\Request;
use App\Events\MessageSent;
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

        $validated = $request->validate([
            'body' => 'required|string|max:255',
        ]);

        $message = Message::create([
            'uuid' => (string) Str::uuid(),
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'body' => $validated['body'],
        ]);

        MessageSent::dispatch($message);

        return back()->with('status', 'Message sent.');
    }
}
