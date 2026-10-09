# Docker

Templates in `assets/docker/`: `Dockerfile`, `docker-compose.prod.yml`, `.dockerignore`.

## Base image choice

| Image | Use |
|---|---|
| `serversideup/php:8.4-fpm-nginx` | default: PHP-FPM + Nginx in one unprivileged container (port 8080), Laravel automations (migrations, optimize) via env vars |
| `serversideup/php:8.4-frankenphp` / `dunglas/frankenphp` | Octane/FrankenPHP worker mode, HTTP/3 |
| `serversideup/php:8.4-cli` | workers/scheduler if you want slimmer role images |

Verify tags and env variable names (e.g. `AUTORUN_ENABLED`, `AUTORUN_LARAVEL_MIGRATION`, `PHP_OPCACHE_ENABLE`) in the serversideup docs for the version you pull. Pin a specific tag in production.

## Dockerfile stages

1. **vendor** — `composer install --no-dev` with lock file (cache-friendly: copy `composer.*` first).
2. **assets** — Node 24: `npm ci && npm run build` (+ `build:ssr`). Wayfinder/typescript-transformer generation needs PHP: either commit generated files, or build assets in a stage that has both PHP and Node.
3. **runtime** — PHP image + extensions (`intl gd exif bcmath pcntl redis pdo_pgsql`), image tools for Media Library, Node binary only if SSR runs in this image, copy app + vendor + `public/build` + `bootstrap/ssr`.

## Compose roles

| Service | Command | Notes |
|---|---|---|
| `app` | image default (Nginx+FPM) | runs migrations + optimize on start (only this one) |
| `worker` | `php artisan horizon` | `stop_signal: SIGTERM`, `stop_grace_period` ≥ longest job |
| `scheduler` | `php artisan schedule:work` | exactly one replica |
| `ssr` | `php artisan inertia:start-ssr` | if SSR enabled; `INERTIA_SSR_URL=http://ssr:13714` |
| `reverb` | `php artisan reverb:start --host=0.0.0.0 --port=8080` | own domain, websockets at proxy |
| `postgres` / `mariadb` | official image | named volume, healthcheck |
| `redis` | `valkey/valkey` or `redis` | AOF on, volume |
| `minio` | `minio/minio` | S3-compatible storage if not using a managed bucket |

## Storage

- Use S3/MinIO for user files so containers stay stateless. If using the local disk, mount `storage/app` as a volume shared by app + worker.
- `php artisan storage:link` only for the `public` disk.

## Local Docker for dev

Sail (`php artisan sail:install`) or a `docker-compose.yml` with the same services plus Mailpit and Vite (`npm run dev -- --host`).

## Build & push (CI)

```bash
docker build -t ghcr.io/<org>/<app>:${GITHUB_SHA} -t ghcr.io/<org>/<app>:latest .
docker push ghcr.io/<org>/<app>:${GITHUB_SHA}
```

Deploy by tag (SHA), keep previous tags for rollback.
