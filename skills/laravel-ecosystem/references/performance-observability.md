# Performance & observability

## Octane (ECO-10)

```bash
composer require laravel/octane
php artisan octane:install      # choose FrankenPHP (recommended), Swoole or RoadRunner
php artisan octane:start --watch   # local
```

Before enabling:
- No static properties holding request data; no singletons capturing `Request`, `Auth::user()`, tenant or config that changes per request → bind as `scoped`.
- Larastan `checkOctaneCompatibility: true`.
- Memory leaks: `--max-requests=500` to recycle workers.
- Test under load (k6, `oha`) before/after; Octane mostly helps CPU-bound bootstrap cost, not slow queries.

## Pulse (self-hosted metrics)

```bash
composer require laravel/pulse
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
php artisan migrate
```

Dashboard `/pulse` (gate `viewPulse`): slow requests, slow queries, slow jobs, exceptions, queue throughput, cache hit rate, server CPU/memory (`php artisan pulse:check` process). Use a separate DB connection or Redis ingest for high traffic.

## Nightwatch (hosted, by Laravel)

Production monitoring of requests, queries, jobs, exceptions, mail, with alerting. Install the agent per the Nightwatch docs; EU clients: review data processing.

## Telescope (local only, ECO-09)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
```

Register only in local (`TelescopeServiceProvider` conditionally, `dont-discover` in composer.json). Never enable in production unless gated, pruned and filtered.

## Debugbar & Pail

- `barryvdh/laravel-debugbar` (dev) — query counts per page; remove from production images.
- `php artisan pail` — tail logs with filters (included in `composer run dev`).

## Error tracking

Sentry (`sentry/sentry-laravel`) or Flare (`spatie/laravel-ignition` + Flare) or Nightwatch. Configure PII scrubbing, release tracking (git SHA), environment tags.

## Caching strategy

- `CACHE_STORE=redis` in production.
- `Cache::flexible()` for dashboards; `Cache::remember()` with tenant-prefixed keys; `Cache::touch()` (L13) to extend TTL.
- Route/config/view/event caches via `php artisan optimize` on deploy.
- HTTP caching for public assets (Vite hashed filenames → long `Cache-Control`).
