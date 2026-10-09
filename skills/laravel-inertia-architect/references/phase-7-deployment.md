# Phase 7 — Performance & Deployment

## Queues & jobs

Put anything slow or external on the queue: mail/notifications, media conversions, webhooks out, PDF generation, imports/exports, AI calls, search indexing, activity fan-out.

```php
final class SendWorkspaceInvite implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 5;
    public array $backoff = [10, 60, 300];
    public int $timeout = 30;

    public function __construct(public Invitation $invitation) {}

    public function uniqueId(): string { return (string) $this->invitation->id; }

    public function handle(): void
    {
        setPermissionsTeamId($this->invitation->workspace_id); // jobs don't run HTTP middleware
        Mail::to($this->invitation->email)->send(new WorkspaceInviteMail($this->invitation));
    }

    public function failed(Throwable $e): void { report($e); }
}
```

- Redis/Valkey queue + **Laravel Horizon** (dashboard, metrics, auto-balancing). Separate queues by priority: `high` (user-facing: invites, password resets), `default`, `media`, `low` (exports, cleanup).
- `ShouldBeUnique` / `WithoutOverlapping` middleware to avoid duplicates; `Bus::batch()` for imports with progress shown via polling or broadcasting.
- `->afterCommit()` (or `after_commit => true` in the queue config) so jobs don't run before the transaction commits.
- Scheduler in `routes/console.php`: `Schedule::command('horizon:snapshot')->everyFiveMinutes(); Schedule::command('model:prune')->daily(); Schedule::command('backup:run')->dailyAt('02:00');`
- Failed jobs: `php artisan queue:failed`, alert via Sentry/Flare/Nightwatch.

## Performance checklist

- [ ] `php artisan optimize` on deploy (config, routes, events, views cache).
- [ ] OPcache enabled with `validate_timestamps=0` in production images.
- [ ] Cache expensive reads (`Cache::flexible()` for stale-while-revalidate), tag by workspace.
- [ ] DB indexes verified with `EXPLAIN` on the 5 heaviest queries.
- [ ] Inertia props trimmed; deferred props for below-the-fold data; prefetch on hover for main navigation.
- [ ] Vite code-splitting per page (default with `@inertiajs/vite` lazy page resolution); check bundle size with `npx vite-bundle-visualizer`.
- [ ] Images via Media Library conversions (webp, responsive), served from S3/CDN.
- [ ] Optional: Laravel Octane (FrankenPHP) for high traffic — audit for state leakage across requests first (singletons holding the current workspace must be `scoped`).
- [ ] Monitoring: Laravel Pulse (self-hosted) or Nightwatch; Telescope only locally.

## Containerised deployment (self-hosted)

Files in `assets/docker/`:

- `Dockerfile` — multi-stage: Node build (assets + SSR bundle) → Composer install → `serversideup/php:8.4-fpm-nginx` runtime with required extensions.
- `docker-compose.prod.yml` — services: `app` (web), `worker` (Horizon), `scheduler`, `ssr`, `reverb` (optional), `postgres`, `redis`, `minio` (optional).
- `.dockerignore`

Same image for `app`, `worker`, `scheduler`, `ssr` — only the command differs. This keeps code in lock-step across processes.

### Dokploy

- Create a **Compose** application pointing at the Git repo and `docker-compose.prod.yml`, or an **Application** using the Dockerfile for the web service plus separate apps for worker/scheduler.
- Dokploy ships its own Traefik reverse proxy with Let's Encrypt: attach the domain to the `app` service port 8080 in Dokploy's Domains tab. Don't also run Nginx Proxy Manager in front of the same domain unless you deliberately chain proxies.
- Environment variables in Dokploy's Environment tab (never commit `.env`).
- Use Dokploy's database services or the compose `postgres` with a named volume; configure Dokploy's scheduled backups or `spatie/laravel-backup` to S3.
- Enable auto-deploy via webhook on push to `main` (after CI passes).

### Nginx Proxy Manager

- Point a Proxy Host at `app:8080` (same Docker network) or `host:port`, enable SSL (Let's Encrypt), "Force SSL", HTTP/2, and **Websockets Support** if Reverb is used (Reverb usually gets its own host, e.g. `ws.example.com` → `reverb:8080`).
- Increase `client_max_body_size` in the advanced tab to match upload limits.

### Laravel behind a proxy

- `$middleware->trustProxies(at: '*')` (or the proxy's network range) so HTTPS, client IP and host are detected — otherwise you get mixed-content and wrong redirect URLs.
- `APP_URL=https://…`, `ASSET_URL` if using a CDN, `SESSION_SECURE_COOKIE=true`.

### Deploy checklist

- [ ] CI green (tests, Larastan, Pint, `npm run build`, `npm run types`, `composer audit`).
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` set, `LOG_CHANNEL=stderr` (container logs).
- [ ] Database: `php artisan migrate --force` runs once per deploy (entrypoint of `app` only, or a one-off release command) — not in worker/scheduler containers.
- [ ] `php artisan optimize` and `php artisan storage:link` (if using the public disk).
- [ ] `php artisan horizon:terminate` after deploy so workers pick up new code (container restart does this too).
- [ ] SSR container healthy (`php artisan inertia:check-ssr`).
- [ ] Health endpoint (`/up` built-in, or spatie/laravel-health) wired to the container healthcheck.
- [ ] Backups scheduled **and a restore tested**.
- [ ] Mail (SMTP/Postmark/Mailgun) DNS: SPF, DKIM, DMARC.
- [ ] Error tracking (Sentry / Flare / Nightwatch) DSN set.
- [ ] Security headers + CSP enforced; HSTS after confirming HTTPS works everywhere.
- [ ] Filament `/admin` restricted (super-admin only; optionally IP allowlist at the proxy).
- [ ] Rollback plan: previous image tag kept; migrations are backwards-compatible for one release.
