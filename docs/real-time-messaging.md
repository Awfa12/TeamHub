# Real-Time Messaging Implementation

## Overview

TeamHub implements real-time messaging using **Laravel Reverb** (WebSocket server), **Laravel Echo** (JavaScript client), and **Livewire 3** (reactive UI). Messages are broadcast to all connected clients instantly without page refresh.

## Architecture

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Browser   │───▶│   Laravel   │───▶│    Queue    │───▶│   Reverb    │
│  (Echo.js)  │    │  (Backend)  │    │   (Redis)   │    │ (WebSocket) │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
       ▲                                                        │
       │                                                        │
       └────────────────────────────────────────────────────────┘
                         WebSocket Push
```

### Flow:
1. User sends a message via Livewire form
2. Laravel creates `Message` model and dispatches `MessageSent` event
3. Queue worker processes the event and sends it to Reverb
4. Reverb broadcasts to all subscribed WebSocket clients
5. Echo.js receives the event and calls Livewire's `messageReceived()` method
6. UI updates instantly without refresh

---

## Key Files

### Backend

| File | Purpose |
|------|---------|
| `app/Events/MessageSent.php` | Broadcast event for new messages |
| `app/Livewire/ChannelChat.php` | Livewire component handling chat UI and real-time updates |
| `app/Models/Message.php` | Message Eloquent model |
| `routes/channels.php` | Broadcast channel authorization |
| `app/Providers/BroadcastServiceProvider.php` | Registers broadcast routes |
| `config/broadcasting.php` | Broadcasting configuration |
| `config/reverb.php` | Reverb WebSocket server configuration |

### Frontend

| File | Purpose |
|------|---------|
| `resources/js/bootstrap.js` | Echo client initialization |
| `resources/views/livewire/channel-chat.blade.php` | Chat UI with `@script` directive for Echo |

---

## Configuration

### Environment Variables (`.env`)

```env
# Broadcasting
BROADCAST_CONNECTION=reverb

# Queue (for async broadcasting)
QUEUE_CONNECTION=redis

# Reverb WebSocket Server
REVERB_APP_ID=teamhub
REVERB_APP_KEY=somekey
REVERB_APP_SECRET=somesecret
REVERB_HOST=reverb
REVERB_PORT=8081
REVERB_SCHEME=http

# Vite (Frontend) - IMPORTANT: Use literal values, not ${VAR} syntax
VITE_REVERB_APP_KEY=somekey
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8081
VITE_REVERB_SCHEME=http
```

> ⚠️ **Important**: Vite doesn't support shell variable interpolation in `.env` files. Use literal values for `VITE_*` variables, not `${REVERB_APP_KEY}`.

### Docker Services

```yaml
# docker-compose.yml
reverb:
  build: ./docker/php
  command: php artisan reverb:start --host=0.0.0.0 --port=8081
  ports:
    - "8081:8081"
  depends_on:
    - app
    - redis

queue:
  build: ./docker/php
  command: php artisan queue:work --tries=3
  depends_on:
    - app
    - redis
```

---

## Implementation Details

### 1. MessageSent Event

```php
// app/Events/MessageSent.php
class MessageSent implements ShouldBroadcast
{
    public Message $message;

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel.' . $this->message->channel_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'uuid' => $this->message->uuid,
            'body' => $this->message->body,
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name,
            ],
            'channel_id' => $this->message->channel_id,
            // ... other fields
        ];
    }
}
```

### 2. Broadcast Channel Authorization

```php
// routes/channels.php
Broadcast::channel('channel.{channelId}', function ($user, $channelId) {
    $channel = Channel::find($channelId);
    
    if (!$channel) return false;
    
    // Check team membership
    if (!$user->teams()->where('teams.id', $channel->team_id)->exists()) {
        return false;
    }
    
    // Public channels: allow all team members
    if (!$channel->is_private) return true;
    
    // Private channels: check channel membership
    return $channel->users()->where('users.id', $user->id)->exists();
});
```

### 3. Echo Client Setup

```javascript
// resources/js/bootstrap.js
import Echo from "laravel-echo";
import Pusher from "pusher-js";
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === "https",
    enabledTransports: ["ws", "wss"],
});
```

### 4. Livewire Component with @script Directive

```php
// app/Livewire/ChannelChat.php
class ChannelChat extends Component
{
    public int $channelId;
    public $chatMessages;

    public function mount(Team $team, Channel $channel)
    {
        $this->channelId = $channel->id;
        $this->chatMessages = Message::with('user')
            ->where('channel_id', $channel->id)
            ->latest()->take(30)->get()->reverse()->values();
    }

    public function sendMessage()
    {
        // Validate and create message
        $message = Message::create([...]);
        
        // Dispatch broadcast event
        MessageSent::dispatch($message);
        
        // Optimistic UI update for sender
        $this->chatMessages->push($message->load('user'));
        $this->body = '';
    }

    public function messageReceived(array $payload): void
    {
        // Avoid duplicates (sender already has the message)
        if ($this->chatMessages->firstWhere('uuid', $payload['uuid'])) {
            return;
        }

        // Fetch actual Message model to maintain collection consistency
        $message = Message::with('user')->find($payload['id']);
        if ($message) {
            $this->chatMessages->push($message);
        }
    }
}
```

```blade
{{-- resources/views/livewire/channel-chat.blade.php --}}
<div>
    {{-- Message list --}}
    @foreach($chatMessages as $message)
        <div>{{ $message->user->name }}: {{ $message->body }}</div>
    @endforeach

    {{-- Send form --}}
    <form wire:submit="sendMessage">
        <textarea wire:model="body"></textarea>
        <button type="submit">Send</button>
    </form>
</div>

@script
<script>
    Echo.private('channel.{{ $channelId }}')
        .listen('.message.sent', (e) => {
            $wire.messageReceived(e);
        });
</script>
@endscript
```

---

## Issues We Fixed

### 1. Vite Environment Variables Not Working

**Problem**: `VITE_REVERB_APP_KEY=${REVERB_APP_KEY}` in `.env` was being read literally as the string `${REVERB_APP_KEY}`.

**Solution**: Use literal values instead of shell interpolation:
```env
# Before (broken)
VITE_REVERB_APP_KEY=${REVERB_APP_KEY}

# After (works)
VITE_REVERB_APP_KEY=somekey
```

### 2. Livewire 3 Echo Listener Not Working

**Problem**: Using `getListeners()` with `echo-private:channel.{id},message.sent` format wasn't binding callbacks properly in Livewire 3.

**Solution**: Use Livewire 3's `@script` directive to manually set up Echo listeners:
```blade
@script
<script>
    Echo.private('channel.{{ $channelId }}')
        .listen('.message.sent', (e) => {
            $wire.messageReceived(e);
        });
</script>
@endscript
```

> **Note**: The `.` prefix before `message.sent` is required when using `broadcastAs()` to specify a custom event name.

### 3. Event Name Format

**Problem**: Console showed "No callbacks on private-channel.1 for message.sent" even though subscription succeeded.

**Solution**: When using `broadcastAs()`, the event name in `.listen()` must have a `.` prefix:
```javascript
// Before (broken)
.listen('message.sent', ...)

// After (works)
.listen('.message.sent', ...)
```

### 4. Livewire 3 Form Syntax

**Problem**: Using Livewire 2 syntax `wire:model.defer` and `wire:submit.prevent` caused issues.

**Solution**: Use Livewire 3 syntax:
```blade
{{-- Before (Livewire 2) --}}
<form wire:submit.prevent="sendMessage">
    <textarea wire:model.defer="body"></textarea>
</form>

{{-- After (Livewire 3) --}}
<form wire:submit="sendMessage">
    <textarea wire:model="body"></textarea>
</form>
```

### 5. Mixed Collection Types Error

**Problem**: `LogicException: Queueing collections with multiple model types is not supported` when pushing stdClass objects into an Eloquent Collection.

**Solution**: Fetch the actual Message model instead of creating a stdClass:
```php
// Before (broken)
$this->chatMessages->push((object) [
    'id' => $payload['id'],
    'body' => $payload['body'],
    // ...
]);

// After (works)
$message = Message::with('user')->find($payload['id']);
if ($message) {
    $this->chatMessages->push($message);
}
```

### 6. diffForHumans() on String Error

**Problem**: `Call to a member function diffForHumans() on string` because broadcast payload has ISO date strings, not Carbon instances.

**Solution**: By fetching the actual Message model (fix #5), dates are automatically cast to Carbon instances by Eloquent.

### 7. Livewire Assets 404

**Problem**: `/livewire/livewire.js` returning 404.

**Solution**: Add Nginx location block:
```nginx
location ~ ^/livewire/(?:livewire\.js|livewire\.min\.js|livewire\.js\.map|livewire\.min\.js\.map)$ {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 8. Broadcasting Config Keys (Laravel 12)

**Problem**: Events were going to `log` driver instead of Reverb.

**Solution**: Laravel 12 uses `BROADCAST_CONNECTION` and `QUEUE_CONNECTION` (not `*_DRIVER`):
```env
# Before (Laravel 11 and earlier)
BROADCAST_DRIVER=reverb
QUEUE_DRIVER=redis

# After (Laravel 12)
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=redis
```

---

## Debugging Tips

### Enable Pusher Debug Logging
```javascript
// resources/js/bootstrap.js (temporarily)
Pusher.logToConsole = true;
```

### Check Queue Processing
```bash
docker compose logs queue --tail=20
```

### Check Reverb Connections
```bash
docker compose logs reverb --tail=20

# Or with debug mode
php artisan reverb:start --host=0.0.0.0 --port=8081 --debug
```

### Verify Broadcasting Config
```bash
docker compose exec app php artisan tinker
>>> config('broadcasting.default')
# Should return: "reverb"
```

### Test Manual Broadcast
```bash
docker compose exec -e HOME=/tmp app php artisan tinker
>>> broadcast(new App\Events\MessageSent(App\Models\Message::first()));
```

---

## Testing Real-Time

1. Open two browser windows (can use incognito for second)
2. Log in as different users (e.g., `owner@example.com` and `admin@example.com`)
3. Navigate both to the same channel
4. Send a message from one browser
5. Message should appear instantly in the other browser without refresh

---

## Next Steps

- [ ] Add typing indicators (presence channels)
- [ ] Add message editing
- [ ] Add message deletion
- [ ] Add file attachments
- [ ] Add emoji reactions
- [ ] Add thread replies

