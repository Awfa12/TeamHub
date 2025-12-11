# TeamHub API & Integration Guide

This document summarizes the primary HTTP routes and real-time channels for TeamHub. It is intended for internal usage and QA, not as a public developer API.

## Base URLs

-   App: `http://localhost:8080`
-   Mailpit UI: `http://localhost:8025`
-   MinIO Console: `http://localhost:9001`

## Authentication

Laravel Breeze (session-based):

-   `POST /login` — `{ email, password }`
-   `POST /register` — `{ name, email, password, password_confirmation }`
-   `POST /logout`
-   Password reset: `POST /forgot-password`, `POST /reset-password`

## Teams

-   `GET /teams` — list teams for current user.
-   `POST /teams` — create team (`name`).
-   `GET /team/{team:slug}` — team detail.

## Channels

-   `GET /team/{team:slug}/channels` — list channels (archived hidden unless `show_archived=1`).
-   `POST /team/{team:slug}/channels` — create channel (`name`, `is_private`, optional `description`).
-   `GET /team/{team:slug}/channel/{channel}` — channel detail + chat.
-   `PATCH /team/{team:slug}/channel/{channel}` — update channel (name, description, privacy, archive toggle).
-   `POST /team/{team:slug}/channel/{channel}/archive` — archive (read-only).
-   `POST /team/{team:slug}/channel/{channel}/unarchive` — unarchive.

## Messaging

-   `POST /team/{team:slug}/channel/{channel}/messages`
    -   Fields: `body` (nullable if file), optional `parent_id` (for replies), `file` (Livewire upload), `uuid` generated server-side.
    -   Behavior: blocked in archived channels (returns 403). Sends `MessageSent` broadcast.
-   Thread replies: same endpoint with `parent_id` set to parent message.
-   Reactions/read receipts/search are handled via Livewire/Echo inside the channel page.

## Files (MinIO via Laravel proxy)

-   `GET /files/{message}` — inline view/preview (auth required).
-   `GET /files/{message}/download` — forced download (auth required).

## Notifications

-   Email: replies and @mentions queued to Mailpit when recipient has `notification_emails = true`.
-   Browser toasts: mentions surfaced via in-app toast (non-sender).

## Real-Time Channels (Laravel Reverb)

-   Presence: `channel.{channelId}` via `Echo.join()`
    -   Events: `.message.sent`, `.message.updated`, `.message.deleted`, `.reaction.toggled`, `.read.receipt`
    -   Whispers: `typing` for typing indicators
-   Broadcast auth is gated by team membership and channel membership for private channels (`routes/channels.php`).

## Testing & Health

-   Full suite: `docker compose exec app php artisan test`
-   Feature coverage highlights: channel access, message flow (post/reply/archive), mail notifications, Breeze auth/profile flows.
