# TeamHub

## Real-Time Team Collaboration Platform

<p align="center">
  <strong>A modern, Slack-inspired collaboration platform built with Laravel 12</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php" alt="PHP 8.4">
  <img src="https://img.shields.io/badge/Livewire-3-FB70A9?style=flat-square" alt="Livewire 3">
  <img src="https://img.shields.io/badge/Tailwind-3-38B2AC?style=flat-square&logo=tailwind-css" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql" alt="MySQL 8.0">
  <img src="https://img.shields.io/badge/Redis-7-DC382D?style=flat-square&logo=redis" alt="Redis">
</p>

---

## 🎯 Project Vision

TeamHub is a **production-ready** team collaboration platform demonstrating modern Laravel architecture patterns:

-   **Real-time messaging** with WebSockets (Laravel Reverb)
-   **Event-driven architecture** with queues and broadcasting
-   **Multi-tenant data isolation** with team-scoped resources
-   **Role-based access control** (Owner → Admin → Member)
-   **Optimistic UI updates** with Livewire 3

> 💡 **Perfect for portfolios**: Open two browsers, send a message, and watch it appear instantly in both—no refresh needed.

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              CLIENT LAYER                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│  Browser (Blade + Livewire 3 + Alpine.js + Tailwind CSS)                    │
│     │                                                                        │
│     ├── Laravel Echo (WebSocket Client)                                     │
│     │      └── Subscribes to private-channel.{id}                           │
│     │                                                                        │
│     └── Livewire Components                                                 │
│            └── @script directive bridges Echo → Livewire methods            │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                            APPLICATION LAYER                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│  Laravel 12 (PHP 8.4)                                                        │
│     │                                                                        │
│     ├── Controllers (TeamController, ChannelController, MessageController)  │
│     ├── Livewire Components (ChannelChat)                                   │
│     ├── Policies (TeamPolicy, ChannelPolicy)                                │
│     ├── Middleware (team.member, channel.access)                            │
│     ├── Events (MessageSent implements ShouldBroadcast)                     │
│     └── Models (User, Team, Channel, Message)                               │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┼───────────────┐
                    ▼               ▼               ▼
┌─────────────────────┐ ┌─────────────────┐ ┌─────────────────────┐
│    MySQL 8.0        │ │     Redis       │ │   Laravel Reverb    │
│    (Primary DB)     │ │  (Cache/Queue)  │ │   (WebSocket)       │
├─────────────────────┤ ├─────────────────┤ ├─────────────────────┤
│ • Users             │ │ • Session Store │ │ • Real-time events  │
│ • Teams             │ │ • Cache Layer   │ │ • Private channels  │
│ • Channels          │ │ • Queue Jobs    │ │ • Presence channels │
│ • Messages          │ │ • Broadcasting  │ │ • Auth middleware   │
│ • Pivot Tables      │ │                 │ │                     │
└─────────────────────┘ └─────────────────┘ └─────────────────────┘
```

---

## 🛠️ Technology Stack

### Backend

| Technology         | Version | Purpose                 |
| ------------------ | ------- | ----------------------- |
| **Laravel**        | 12.x    | PHP Framework           |
| **PHP**            | 8.4     | Runtime                 |
| **MySQL**          | 8.0     | Primary Database        |
| **Redis**          | 7.x     | Cache, Sessions, Queues |
| **Laravel Reverb** | 1.x     | WebSocket Server        |

### Frontend

| Technology       | Purpose                  |
| ---------------- | ------------------------ |
| **Livewire 3**   | Reactive Components      |
| **Alpine.js**    | Lightweight JS Framework |
| **Tailwind CSS** | Utility-First Styling    |
| **Laravel Echo** | WebSocket Client         |
| **Pusher.js**    | Echo Transport Layer     |

### DevOps

| Service     | Port | Purpose               |
| ----------- | ---- | --------------------- |
| **Nginx**   | 8080 | Web Server            |
| **PHP-FPM** | 9000 | PHP Process Manager   |
| **Reverb**  | 8081 | WebSocket Server      |
| **Mailpit** | 8025 | Email Testing         |
| **MinIO**   | 9001 | S3-Compatible Storage |

### Quality & Testing

| Area                  | Status | Notes                                                                       |
| --------------------- | ------ | --------------------------------------------------------------------------- |
| Feature tests         | ✅     | Channel access, message flow (post/reply/archive block), mail notifications |
| Auth/Profile (Breeze) | ✅     | Login, register, email verify, password reset/update, profile update/delete |
| Test harness          | ✅     | CSRF disabled in tests, queue sync, session/cache array drivers             |
| Command to run        | ✅     | `docker compose exec app php artisan test`                                  |

---

## 📊 Data Model

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│    Users    │       │    Teams    │       │  Channels   │
├─────────────┤       ├─────────────┤       ├─────────────┤
│ id          │──┐    │ id          │──┐    │ id          │
│ name        │  │    │ name        │  │    │ team_id     │───┐
│ email       │  │    │ slug        │  │    │ name        │   │
│ password    │  │    │ owner_id    │──┼──┐ │ slug        │   │
│ timestamps  │  │    │ settings    │  │  │ │ description │   │
└─────────────┘  │    │ active      │  │  │ │ is_private  │   │
                 │    │ timestamps  │  │  │ │ creator_id  │───┼──┐
                 │    └─────────────┘  │  │ │ archived    │   │  │
                 │           │         │  │ │ timestamps  │   │  │
                 │           ▼         │  │ └─────────────┘   │  │
                 │    ┌─────────────┐  │  │        │          │  │
                 │    │  team_user  │  │  │        ▼          │  │
                 │    ├─────────────┤  │  │ ┌─────────────┐   │  │
                 └───▶│ team_id     │◀─┘  │ │channel_user │   │  │
                 ┌───▶│ user_id     │     │ ├─────────────┤   │  │
                 │    │ role        │     │ │ channel_id  │◀──┘  │
                 │    │ joined_at   │     │ │ user_id     │◀─────┼──┐
                 │    │ last_seen   │     │ │ role        │      │  │
                 │    │ timestamps  │     │ │ joined_at   │      │  │
                 │    └─────────────┘     │ │ timestamps  │      │  │
                 │                        │ └─────────────┘      │  │
                 │                        │                      │  │
                 │    ┌─────────────┐     │                      │  │
                 │    │  Messages   │     │                      │  │
                 │    ├─────────────┤     │                      │  │
                 │    │ id          │     │                      │  │
                 │    │ uuid        │     │                      │  │
                 │    │ channel_id  │◀────┘                      │  │
                 └────│ user_id     │◀──────────────────────────┘  │
                      │ body        │                              │
                      │ file_*      │                              │
                      │ edited_at   │                              │
                      │ deleted_at  │                              │
                      │ timestamps  │                              │
                      └─────────────┘                              │
                                                                   │
Roles: owner │ admin │ member ─────────────────────────────────────┘
```

---

## 🔐 Authorization Model

### Team Roles & Permissions

| Action                  | Owner | Admin | Member |
| ----------------------- | :---: | :---: | :----: |
| View team               |  ✅   |  ✅   |   ✅   |
| Update team settings    |  ✅   |  ✅   |   ❌   |
| Delete team             |  ✅   |  ❌   |   ❌   |
| Manage members          |  ✅   |  ✅   |   ❌   |
| Create channels         |  ✅   |  ✅   |   ❌   |
| Update/archive channels |  ✅   |  ✅   |   ❌   |
| Delete channels         |  ✅   |  ❌   |   ❌   |
| Send messages           |  ✅   |  ✅   |   ✅   |

### Channel Access

| Channel Type | Access Rule                                              |
| ------------ | -------------------------------------------------------- |
| **Public**   | All team members can view and send messages              |
| **Private**  | Only explicitly added members can view and send messages |

### How Roles Work (Pivot Tables)

Roles are **not stored on the User model**. Instead, they're stored in **pivot tables**, allowing users to have different roles in different teams.

#### Team Roles (`team_user` pivot table)

```sql
team_user
├── team_id      -- FK to teams
├── user_id      -- FK to users
├── role         -- 'owner' | 'admin' | 'member'
├── joined_at    -- When user joined
└── timestamps
```

**Example**: Alice can be `owner` of "Acme Corp" but just a `member` of "Design Collective".

#### Channel Roles (`channel_user` pivot table)

```sql
channel_user
├── channel_id   -- FK to channels
├── user_id      -- FK to users
├── role         -- 'owner' | 'participant'
├── joined_at    -- When user was added
└── timestamps
```

> **Note**: `channel_user` is only used for **private channels** to track membership.

#### Accessing Roles in Code

```php
// Get user's role in a specific team
$role = $user->teams()
    ->where('team_id', $team->id)
    ->first()
    ->pivot
    ->role;

// Check if user is admin or owner
$isAdmin = $user->teams()
    ->where('team_id', $team->id)
    ->whereIn('role', ['owner', 'admin'])
    ->exists();

// Attach user to team with role
$user->teams()->attach($team->id, [
    'role' => 'member',
    'joined_at' => now(),
]);
```

#### Model Relationships

```php
// User.php
public function teams(): BelongsToMany
{
    return $this->belongsToMany(Team::class, 'team_user')
        ->withPivot('role', 'joined_at', 'last_seen_at')
        ->withTimestamps();
}

// Team.php
public function users(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'team_user')
        ->withPivot('role', 'joined_at', 'last_seen_at')
        ->withTimestamps();
}
```

---

## ⚡ Real-Time Features

### Message Broadcasting Flow

```
1. User sends message via Livewire form
           │
           ▼
2. ChannelChat Livewire creates Message model
           │
           ▼
3. MessageSent event dispatched to queue
           │
           ▼
4. Queue worker processes event
           │
           ▼
5. Reverb broadcasts to presence-channel.{id}
           │
           ▼
6. Echo.join() receives event on subscribed clients
           │
           ▼
7. Alpine dispatches event → $wire.messageReceived()
           │
           ▼
8. UI updates instantly (no page refresh)
```

### Typing Indicators Flow

```
1. User types in textarea
           │
           ▼
2. Debounced input event (300ms)
           │
           ▼
3. Alpine sends whisper via presence channel
           │
           ▼
4. Reverb relays whisper to other clients (no server)
           │
           ▼
5. Other clients receive whisper event
           │
           ▼
6. UI shows "X is typing..." with animated dots
           │
           ▼
7. Auto-hide after 2 seconds of no activity
```

### Online Presence Flow

```
1. User opens channel page
           │
           ▼
2. Echo.join() subscribes to presence-channel.{id}
           │
           ▼
3. .here(users) callback receives current viewers
           │
           ▼
4. .joining(user) fires when new user joins
           │
           ▼
5. .leaving(user) fires when user leaves/disconnects
           │
           ▼
6. Alpine updates onlineUsers array reactively
           │
           ▼
7. UI shows avatars + "X online" indicator
```

### Implemented Features

-   ✅ Real-time message delivery
-   ✅ Optimistic UI updates for sender
-   ✅ Duplicate message prevention (UUID-based)
-   ✅ Presence channel authorization
-   ✅ Typing indicators with whispers
-   ✅ Auto-scroll to new messages
-   ✅ Input clearing after send
-   ✅ Online presence (who's viewing the channel)
-   ✅ Message editing with "(edited)" indicator
-   ✅ Message deletion with custom modal
-   ✅ File attachments with MinIO storage
-   ✅ Image previews with download links
-   ✅ Thread replies (Slack-style flat threading)
-   ✅ Emoji reactions (messages + replies)
-   ✅ Read receipts (messages + replies, excludes self)
-   ✅ Message search (channel-scoped, replies included)
-   ✅ Notifications (email replies/@mentions with opt-in, browser toasts for mentions)
-   ✅ Channel archive filtering (hidden by default, toggle to show, read-only banner, send blocked)
-   ✅ Emoji reactions (👍 ❤️ 😂 😮 😢 🎉)

### Planned Features

-   ⬜ Read receipts
-   ⬜ Message search

---

## 🐳 Docker Environment

### Services Overview

```yaml
services:
    app: # PHP-FPM 8.4 with extensions
    nginx: # Web server (port 8080)
    db: # MySQL 8.0 (port 3306)
    redis: # Cache/Queue/Session (port 6379)
    queue: # Laravel queue worker
    reverb: # WebSocket server (port 8081)
    mailpit: # Email testing (port 8025)
    minio: # S3-compatible storage (port 9001)
```

### Quick Start

```bash
# Clone and setup
git clone <repo>
cd teamhub
cp .env.example .env

# Start containers
docker compose up -d

# Install dependencies
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

# Frontend
npm install
npm run dev

# Access
http://localhost:8080
```

### Test Accounts

| Role   | Email              | Password |
| ------ | ------------------ | -------- |
| Owner  | owner@example.com  | password |
| Admin  | admin@example.com  | password |
| Member | member@example.com | password |

---

## 📁 Project Structure

```
teamhub/
├── app/
│   ├── Events/
│   │   └── MessageSent.php          # Broadcast event
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── TeamController.php
│   │   │   ├── ChannelController.php
│   │   │   └── MessageController.php
│   │   └── Middleware/
│   │       ├── EnsureUserBelongsToTeam.php
│   │       └── EnsureUserCanAccessChannel.php
│   ├── Livewire/
│   │   └── ChannelChat.php          # Real-time chat component
│   ├── Models/
│   │   ├── User.php
│   │   ├── Team.php
│   │   ├── Channel.php
│   │   └── Message.php
│   ├── Policies/
│   │   ├── TeamPolicy.php
│   │   └── ChannelPolicy.php
│   └── Providers/
│       ├── AuthServiceProvider.php
│       └── BroadcastServiceProvider.php
├── config/
│   ├── broadcasting.php
│   └── reverb.php
├── database/
│   ├── migrations/
│   └── seeders/
│       └── TeamSeeder.php
├── docker/
│   ├── nginx/
│   │   └── site.conf
│   └── php/
│       ├── Dockerfile
│       └── php.ini
├── docs/
│   ├── project-overview.md          # This file
│   ├── progress.md                  # Build progress
│   └── real-time-messaging.md       # Implementation details
├── resources/
│   ├── js/
│   │   ├── app.js
│   │   └── bootstrap.js             # Echo configuration
│   └── views/
│       ├── channels/
│       ├── livewire/
│       │   └── channel-chat.blade.php
│       └── teams/
├── routes/
│   ├── web.php
│   └── channels.php                 # Broadcast authorization
├── docker-compose.yml
└── .env
```

---

## 🎬 Demo Walkthrough

### 1. Multi-User Real-Time Chat

```
1. Open browser #1 → Login as owner@example.com
2. Open browser #2 (incognito) → Login as admin@example.com
3. Both navigate to: /team/demo-team/channel/1
4. Send message from browser #1
5. ✨ Message appears instantly in browser #2
```

### 2. Role-Based Access

```
1. Login as member@example.com
2. Notice: No "Create Channel" button (members can't create)
3. Notice: No "Manage Channel" section (members can't edit)
4. Login as owner@example.com
5. Full access to all management features
```

### 3. Private Channels

```
1. Login as member@example.com
2. Navigate to /team/demo-team/channels
3. Notice: "leadership" channel not visible (private, not a member)
4. Login as owner@example.com
5. "leadership" channel is visible (owner is a member)
```

---

## 📚 Documentation

| Document                                                | Description                                 |
| ------------------------------------------------------- | ------------------------------------------- |
| [`docs/progress.md`](progress.md)                       | Build progress and completed tasks          |
| [`docs/real-time-messaging.md`](real-time-messaging.md) | Detailed real-time implementation guide     |
| [`docs/api.md`](api.md)                                 | HTTP routes, files, real-time channels      |
| [`docs/deploy-hostinger.md`](deploy-hostinger.md)       | Production deployment guide (Hostinger VPS) |

---

## 🚀 Roadmap

### Phase 1: Foundation ✅

-   [x] Docker development environment
-   [x] Authentication (Laravel Breeze)
-   [x] Teams & Channels CRUD
-   [x] Role-based permissions
-   [x] Real-time messaging

### Phase 2: Enhanced Messaging ✅

-   [x] Typing indicators
-   [x] Online presence
-   [x] Message editing
-   [x] Message deletion
-   [x] File attachments (MinIO)
-   [x] Image previews

### Phase 3: Advanced Features ✅

-   [x] Thread replies
-   [x] Emoji reactions
-   [x] Read receipts
-   [x] Message search
-   [x] Notifications
-   [x] Channel archive filtering

### Phase 4: Production Ready

-   [x] Unit & feature tests
-   [x] API/documentation (internal)
-   [x] Performance optimization (indexes, gzip/static caching)
-   [ ] Production deployment guide

---

## 📄 License

This project is open-sourced for educational and portfolio purposes.

---

<p align="center">
  Built with ❤️ using Laravel, Livewire, and Reverb
</p>
