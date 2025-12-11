# Production Deployment Guide (Hostinger VPS)

This guide assumes an Ubuntu-based VPS with Docker. It uses the existing Docker Compose stack (app, nginx, db, redis, queue, reverb, mailpit, minio).

## 1) VPS prep

-   Update system: `sudo apt-get update && sudo apt-get upgrade -y`
-   Install Docker + Compose plugin (Ubuntu):  
    `curl -fsSL https://get.docker.com | sh`  
    `sudo apt-get install -y docker-compose-plugin`
-   Add your user to docker group: `sudo usermod -aG docker $USER` (relogin)

## 2) Clone and env

```bash
git clone <repo> teamhub
cd teamhub
cp .env.example .env
```

Set in `.env`:

-   `APP_ENV=production`
-   `APP_URL=https://your-domain.com`
-   `SESSION_DOMAIN=your-domain.com` (optional)
-   `QUEUE_CONNECTION=redis`
-   `BROADCAST_CONNECTION=reverb`
-   `CACHE_STORE=redis`
-   `SESSION_DRIVER=redis`
-   Reverb frontend: `VITE_REVERB_HOST=your-domain.com`, `VITE_REVERB_SCHEME=https`, `VITE_REVERB_PORT=443`
-   Reverb server: `REVERB_HOST=reverb`, `REVERB_SCHEME=http`, `REVERB_PORT=8081` (internal)
-   Mail: point to real SMTP or keep Mailpit for staging
-   MinIO: keep defaults for staging; for prod S3, update AWS\_\* accordingly.

## 3) Build and start

```bash
docker compose pull          # if using remote images
docker compose build         # build PHP/Nginx images
docker compose up -d
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm ci
docker compose exec app npm run build
```

## 4) Production caches

Inside the app container:

```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

Or run `scripts/deploy.sh` inside the app container after npm build.

## 5) SSL / domain

-   Simplest: terminate TLS at the Hostinger dashboard / proxy (Cloudflare/Fastly) pointing to VPS port 80.
-   Or install certbot on the host and reverse-proxy 80/443 to the nginx container. Expose 80/443 in compose and mount certs into `docker/nginx`.
-   Ensure `VITE_REVERB_SCHEME=https` and `VITE_REVERB_PORT=443` for browser Echo connections.

## 6) Services/ports

-   App/nginx: 80 (externally exposed; terminate TLS at 443 if using host proxy)
-   Reverb WS: internal 8081 (browser connects via domain:443 wss)
-   Mailpit: 8025 (dev only; block in prod)
-   MinIO: 9001 (dev only; block in prod or replace with S3)

## 7) Health checks

-   Auth-protected: `/health/queue`, `/health/reverb`, `/health/db`
-   Use for uptime monitors (session cookie required).

## 8) Logs & monitoring

```bash
docker compose logs -f app
docker compose logs -f queue
docker compose logs -f reverb
docker compose logs -f nginx
```

## 9) Backups

-   Database: regular `mysqldump` from the db container (bind a volume).
-   Files: MinIO data volume or switch to S3 for managed durability.

## 10) Security hardening (high-level)

-   Disable/lock down Mailpit and MinIO in production or restrict by firewall.
-   Set strong DB/Mail credentials in `.env`.
-   Keep host firewall (ufw) allowing 22/80/443 only; block 8025/9001 externally.
-   Run `composer install --no-dev` and keep images up to date (`docker compose pull`).

## 11) Zero-downtime tips

-   Use `docker compose pull && docker compose up -d --no-deps app queue reverb nginx` for rolling updates.
-   Run migrations before/with deploy; if breaking, use maintenance mode: `php artisan down` / `up`.
