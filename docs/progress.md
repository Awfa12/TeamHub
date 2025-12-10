## TeamHub Build Progress

Date: 2025-12-10 (dev environment)

### Completed

-   Docker stack (PHP 8.4) with app (php-fpm), nginx, mysql 8.0, redis, queue worker, reverb, mailpit, minio. PHP image includes pdo_mysql, mbstring, bcmath, intl, pcntl, opcache, zip, xml, gd, redis. Nginx pointed to `public/` with try_files to index.php. Env set to service hostnames; storage/cache perms adjusted for Windows bind mounts.
-   Dev tooling: Mailpit on 8025/1025, MinIO on 9001/9000 with bucket `teamhub`, Reverb installed and running on 8081.
-   Auth & domain schema: migrations for teams, team_user pivot (roles owner/admin/member), channels (public/private), channel_user pivot; models wired with relations, casts, `ownedTeams` on User.
-   Policies & guards: TeamPolicy (view/viewAny/create/update/delete/manageMembers with roles), ChannelPolicy (view with team/private checks; create/update/delete for owner/admin). Middleware `team.member` and `channel.access` registered via bootstrap/app.php with proper web/api stacks restored.
-   Routing/UI: Team/channel routes nested by `{team:slug}` with access middleware. Blade views for dashboard, teams index/show, channels index/show using `<x-app-layout>`; channel create form hidden for non-admin/owner; manage link gated. Navbar has persistent “Teams” link.
-   Controllers: TeamController and ChannelController using AuthorizesRequests; channel listing respects public/private membership; channel create/update/archive actions authorized; slug uniqueness per team.
-   Seed data: users for each role (owner/admin/member), demo team, public `general` and private `leadership` channel; private channel membership for owner/admin.
-   Breeze installed (Blade), npm build done, migrations seeded.
-   Messaging foundation: messages table/model/factory; `MessageSent` broadcast event; `MessageController@store` (auth checks, UUID, dispatch event); channel show page has a basic send form; broadcast queue running on Redis; Reverb service confirmed running.

### Pending Checks

-   None (baseline dev setup and RBAC scaffolding are done).

### Next Implementation Steps

-   Add flash/status messages to channel manage actions (update/archive).
-   Wire real-time messaging: Livewire chat component + Echo/Reverb listeners on `private-channel.{id}`; render history and append on `message.sent`.
-   Add channel archive filtering in lists (hide archived by default).
-   Optional: env flag to restrict who can create teams; Mailpit test mail in a feature test; health checks for queue/reverb.
