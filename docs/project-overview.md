## TeamHub – Real-Time Collaboration Platform

### What it is
- Lightweight Slack-style app for teams: workspaces (teams), channels, DMs later, real-time chat, presence, file sharing, permissions.
- Built to showcase modern Laravel real-time (Reverb), queues, and multi-tenancy by team.

### Why it matters
- Demonstrates event-driven architecture (WebSockets, queues) with instant UX.
- Shows RBAC, multi-tenant data isolation, and background processing for uploads.
- Recruiter-friendly demo: open two browsers and messages/presence sync instantly.

### Tech stack
- Backend: Laravel 12, PHP 8.4, MySQL 8.0 (team/channel/message data), Redis (cache/queue/session).
- Realtime: Laravel Reverb + Echo (WS server at 8081).
- UI: Blade + Livewire (chat state), Tailwind.
- Files: MinIO (S3-compatible) in dev; S3 in prod.
- Mail: Mailpit in dev.

### Core features (target)
- Multi-team workspaces with roles: owner, admin, member (Spatie permissions).
- Channels (public/private) scoped to teams; private membership via pivot.
- Messaging with optimistic UI, edits, soft deletes, read receipts.
- Presence channels per team; typing indicators.
- File uploads via queue jobs, S3/MinIO storage, thumbnails for images.
- Auth scaffold (Breeze) with team context in URLs: `/team/{slug}/channel/{id}`.

### Architecture notes
- Multi-tenancy: single DB, team scoping enforced via middleware/policies; URLs carry team slug.
- Broadcasting: events (MessageSent, FileUploaded, Typing, Presence) to private/presence channels; auth via broadcasting routes.
- Queues: Redis-backed workers for uploads and other async tasks.
- Storage: use `FILESYSTEM_DISK=s3` with path-style endpoints for MinIO in dev.

### Data model (high level)
- Users, Teams, team_user pivot (role, joined_at, last_seen).
- Channels (team_id, is_private, creator_id), channel_user pivot for private access.
- Messages (channel_id, user_id, body, file fields, uuid, edited_at, soft delete).
- Read receipts (message_id, user_id, read_at).

### Dev environment (Docker)
- Services: app (php-fpm), nginx (8080), mysql (3306), redis (6379), queue worker, reverb (8081), mailpit (8025/1025), minio (9000/9001).
- `.env` aligns to service names (db, redis, reverb, mailpit, minio) with Redis for cache/session/queue.

### Demo storyline
- Open two browsers on the same team: send messages, see instant delivery and presence.
- Upload a file: message shows “uploading” then final link/preview after queue job.
- Switch channels/teams to show isolation and permissions.

