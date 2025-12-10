## TeamHub Build Progress

Date: 2025-12-10 (dev environment)

### Completed

-   Docker stack (PHP 8.4) with app (php-fpm), nginx, mysql 8.0, redis, queue worker, reverb, mailpit, minio. PHP image includes pdo_mysql, mbstring, bcmath, intl, pcntl, opcache, zip, xml, gd, redis. Nginx pointed to `public/` with try_files to index.php. Env set to service hostnames; storage/cache perms adjusted for Windows bind mounts.
-   Dev tooling: Mailpit on 8025/1025, MinIO on 9001/9000 with bucket `teamhub`, Reverb installed and running on 8081.
-   Auth & domain schema: migrations for teams, team_user pivot (roles owner/admin/member), channels (public/private), channel_user pivot; models wired with relations, casts, `ownedTeams` on User.
-   Policies & guards: TeamPolicy (view/viewAny/create/update/delete/manageMembers with roles), ChannelPolicy (view with team/private checks; create/update/delete for owner/admin). Middleware `team.member` and `channel.access` registered via bootstrap/app.php with proper web/api stacks restored.
-   Routing/UI: Team/channel routes nested by `{team:slug}` with access middleware. Blade views for dashboard, teams index/show, channels index/show using `<x-app-layout>`; channel create form hidden for non-admin/owner; manage link gated. Navbar has persistent "Teams" link.
-   Controllers: TeamController and ChannelController using AuthorizesRequests; channel listing respects public/private membership; channel create/update/archive actions authorized; slug uniqueness per team.
-   Seed data: users for each role (owner/admin/member), demo team, public `general` and private `leadership` channel; private channel membership for owner/admin.
-   Breeze installed (Blade), npm build done, migrations seeded.
-   **Real-time messaging fully working:**
    -   `messages` table/model with UUID, body, file fields, soft deletes
    -   `MessageSent` broadcast event implementing `ShouldBroadcast`
    -   `MessageController@store` with auth checks, UUID generation, event dispatch
    -   `ChannelChat` Livewire component with `@script` directive for Echo listener
    -   Echo client configured in `bootstrap.js` with Reverb broadcaster
    -   Broadcast channel authorization in `routes/channels.php`
    -   Queue worker processing broadcast events via Redis
    -   Reverb WebSocket server pushing events to connected clients
    -   Messages appear instantly in all connected browsers without refresh
    -   See `docs/real-time-messaging.md` for full implementation details and fixes

### Key Fixes Applied for Real-Time

1. **Vite env vars**: Use literal values for `VITE_*` (no `${VAR}` interpolation)
2. **Livewire 3 Echo**: Use `@script` directive instead of `getListeners()` for Echo
3. **Event name**: `.listen('.message.sent')` needs `.` prefix with `broadcastAs()`
4. **Livewire 3 syntax**: `wire:submit` and `wire:model` (no `.prevent`/`.defer`)
5. **Collection types**: Fetch actual Message model, not stdClass, to avoid serialization errors
6. **Laravel 12 config**: `BROADCAST_CONNECTION` and `QUEUE_CONNECTION` (not `*_DRIVER`)
7. **Nginx for Livewire**: Added location block for `/livewire/*.js` assets

### Pending Checks

-   None (baseline dev setup, RBAC scaffolding, and real-time messaging are done).

### Next Implementation Steps

-   Add flash/status messages to channel manage actions (update/archive).
-   Add channel archive filtering in lists (hide archived by default).
-   Add typing indicators using presence channels.
-   Add message editing and deletion.
-   Add file attachments (integrate with MinIO).
-   Optional: env flag to restrict who can create teams; Mailpit test mail in a feature test; health checks for queue/reverb.
