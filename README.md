# 🛰️ TeamHub — Real-Time Team Collaboration Platform

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-3-4E56A6?style=for-the-badge)
![Reverb](https://img.shields.io/badge/Reverb-WebSockets-8A2BE2?style=for-the-badge)
![Redis](https://img.shields.io/badge/Redis-Cache%2FQueue-DC382D?style=for-the-badge&logo=redis&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Tailwind](https://img.shields.io/badge/Tailwind-3-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![CI](https://img.shields.io/github/actions/workflow/status/<REPO_OWNER>/<REPO_NAME>/ci.yml?style=for-the-badge&label=Tests)

**Slack-inspired, multi-tenant team chat with real-time messaging, presence, typing indicators, threads, reactions, read receipts, search, notifications, and secure file sharing.**

[Overview](#-overview) • [Features](#-key-features) • [Tech](#-tech-stack) • [Install](#-installation) • [Docs](#-docs) • [Testing](#-testing) • [Roadmap](#-roadmap)

</div>

---

## 🎯 Overview

TeamHub is a production-ready collaboration platform built with Laravel 12 + Livewire 3, using Reverb for WebSockets, Redis for queues/sessions/cache, MySQL for persistence, and MinIO for file storage. It ships with strong RBAC, presence channels, threading, reactions, read receipts, search, and email/browser notifications.

### What makes it special

-   ⚡ Real-time everything: presence channels, whispers for typing, optimistic UI
-   🧵 Threads & replies: Slack-style flat threading with @mention pre-fill
-   🔐 RBAC & privacy: owner/admin/member roles, private channels, archive read-only
-   📂 Files & previews: MinIO-backed uploads with secure Laravel proxy download
-   🔔 Notifications: Mailpit emails for replies/@mentions + in-app mention toasts
-   🔍 Search & jump: channel-scoped search with “Go to message” navigation

---

## ✨ Key Features

-   Messaging UX: optimistic send, auto-scroll, input clear, inline edit, soft delete + confirmation modal
-   Presence & typing: who’s online, typing indicators with debounced whispers
-   Threads & replies: lazy-loaded replies, jump-to, reply-to-reply with @prefill
-   Reactions: emoji toggle (messages + replies), tooltips, grouped counts
-   Read receipts: per message/reply, excludes self, hover list
-   Files: Livewire uploads → MinIO, image previews, secure downloads
-   Search: channel-scoped, replies included, “Go to” jump
-   Notifications: email (replies/@mentions, opt-in flag), browser toasts for mentions
-   Archive handling: archived channels hidden by default; toggle to show; send blocked server/UI

---

## 🛠 Tech Stack

**Backend:** Laravel 12, PHP 8.4, MySQL 8.0, Redis, Reverb (WebSockets)  
**Frontend:** Blade + Livewire 3, Alpine.js (bundled), Tailwind CSS, Echo + Pusher transport  
**Storage:** MinIO (S3-compatible) via dedicated disk, Laravel file proxy for secure downloads  
**DevOps:** Docker Compose (app, nginx, db, redis, queue, reverb, mailpit, minio)

---

## 🏗 Architecture (high level)

```
Browser (Blade + Livewire + Alpine)
   │  Echo join/whisper (presence: channel.{id})
   ▼
Laravel 12 app (Reverb broadcaster, Redis queue/cache/session)
   │  Events: MessageSent/Updated/Deleted, ReactionToggled, ReadReceiptUpdated
   ▼
MySQL (teams, channels, messages, reactions, reads)
MinIO (file storage via Laravel disk + proxy routes)
Reverb (WebSocket server)  Redis (queue + cache + session)
```

---

## 🚀 Installation

```bash
git clone <repo>
cd teamhub
cp .env.example .env              # ensure hosts/ports match docker-compose
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
npm install
npm run dev                       # or npm run build for production
```

Services:

-   App: http://localhost:8080
-   Mailpit: http://localhost:8025
-   MinIO Console: http://localhost:9001
-   Reverb WS: ws://localhost:8081

---

## 📁 Project Structure (high level)

-   `app/Livewire/ChannelChat.php` — real-time chat component
-   `app/Events/*` — broadcasts (sent/updated/deleted/reaction/read)
-   `app/Http/Controllers/MessageController.php` — message create, archive guard, notifications
-   `app/Policies/*` — team/channel RBAC
-   `database/seeders/TeamSeeder.php` — rich seed data (users/teams/channels/messages)
-   `resources/views/livewire/channel-chat.blade.php` — chat UI (typing, presence, threads, reactions, reads, files)
-   `docker-compose.yml` & `docker/` — app, nginx, db, redis, queue, reverb, mailpit, minio
-   `docs/` — progress, overview, real-time guide, API quickref

---

## 🧪 Testing

-   Full suite: `docker compose exec app php artisan test`
-   Coverage highlights: channel access, message flow (post/reply/archive block), mail notifications, Breeze auth/profile flows
-   Harness: CSRF disabled in tests, queue sync, session/cache array drivers; factories for Team/Channel/MessageRead

---

## 📚 Docs

-   Progress log: `docs/progress.md`
-   Architecture & data model: `docs/project-overview.md`
-   Real-time messaging guide: `docs/real-time-messaging.md`
-   API & integration quickref: `docs/api.md`
-   Production deploy (Hostinger VPS): `docs/deploy-hostinger.md`

---

## 👥 Sample Accounts (seeded)

All passwords: `password`

-   alice@example.com (owner/admin across teams)
-   bob@example.com (owner/admin across teams)
-   charlie@example.com, diana@example.com, eve@example.com, frank@example.com
-   owner@example.com / admin@example.com / member@example.com (Demo Team roles)

---

## 🏁 Production Notes (current focus)

-   Env: `QUEUE_CONNECTION=redis`, `BROADCAST_CONNECTION=reverb`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, Mailpit/SMTP set
-   Indexes: `messages(channel_id, created_at)`, `messages(parent_id, created_at)`, `reactions(message_id)`, `message_reads(message_id)` (added)
-   Health checks: `/health/queue`, `/health/reverb` (Redis ping), `/health/db` (MySQL ping) — behind auth
-   Build: `php artisan config:cache route:cache view:cache` + `npm run build` (see `scripts/deploy.sh`)
-   Nginx: gzip enabled for static assets; cache headers for CSS/JS/fonts/images

---

## 🗺 Roadmap (summary)

-   Phase 1: Foundation ✅
-   Phase 2: Enhanced Messaging ✅
-   Phase 3: Advanced Features ✅
-   Phase 4: Production hardening 🔄 (tests done; perf/docs/deploy in progress)

---

## 📄 License

Open-sourced for educational and portfolio purposes.
