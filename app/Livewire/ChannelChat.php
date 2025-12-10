<?php

namespace App\Livewire;

use App\Models\Team;
use App\Models\Channel;
use App\Models\Message;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use App\Events\MessageSent;

class ChannelChat extends Component
{
    use AuthorizesRequests;

    public Team $team; 
    public Channel $channel; 
    public int $teamId;
    public int $channelId;
    public $body = ''; 
    public $chatMessages;

    public function mount(Team $team, Channel $channel)
    {
        $this->team = $team;
        $this->channel = $channel;
        $this->teamId = $team->id;
        $this->channelId = $channel->id;
        $this->chatMessages = Message::with('user')
                        ->where('channel_id', $channel->id)
                        ->latest()->take(30)->get()->reverse()->values();
    }

    public function sendMessage()
    {
        // Authorize user to view team and channel
        $team = Team::findOrFail($this->teamId);
        $channel = Channel::findOrFail($this->channelId);

        $this->authorize('view', $team);
        $this->authorize('view', $channel);

        // Validate body
        $validated = $this->validate([
            'body' => 'required|string|max:500',
        ]);

        $userId = auth()->id();
        if (! $userId) {
            abort(401);
        }

        $message = Message::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $userId,
            'channel_id' => $channel->id,
            'body' => $validated['body'],
        ]);

        MessageSent::dispatch($message);

        $this->chatMessages->push($message->load('user'));

        // Clear the input
        $this->body = '';
    }

    public function messageReceived(array $payload): void
    {
        // avoid duplicates
        if ($this->chatMessages->firstWhere('uuid', $payload['uuid'])) {
            return;
        }

        // Fetch the actual Message model to maintain collection type consistency
        $message = Message::with('user')->find($payload['id']);
        
        if ($message) {
            $this->chatMessages->push($message);
        }
    }
    public function render()
    {
        return view('livewire.channel-chat');
    }
}
