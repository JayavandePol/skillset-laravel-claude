---
name: laravel-deployment
description: Ship Laravel apps to production safely — Docker images (serversideup/php, FrankenPHP), docker-compose with web/worker/scheduler/SSR/Reverb roles, Dokploy, Nginx Proxy Manager, Traefik, Laravel Forge, Laravel Cloud, Ploi and shared hosting, environment/secrets, migrations on deploy, zero-downtime releases, CI/CD with GitHub Actions, performance tuning (OPcache, caching, Octane) and the go-live checklist. Use this whenever the user deploys, containerises, sets up CI/CD, configures a server or reverse proxy, prepares for production/launch, or debugs production-only issues (mixed content, wrong URLs, queues not running, assets 404) in a Laravel app.
---

# Laravel Deployment

Production must be boring: reproducible builds, one image for every role, secrets outside git, automated checks before anything ships, and a tested way back.

## Rules

- **DEP-01** Builds are reproducible: `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, built in CI, deployed as an immutable image/tag (never `git pull` + build on the server by hand).
- **DEP-02** One image, many roles: web, queue worker (Horizon), scheduler, SSR, Reverb — same code version, different command.
- **DEP-03** Migrations run exactly once per deploy (release step or web container only), with `--force`, and are backwards-compatible with the previous release (DB-05).
- **DEP-04** `php artisan optimize` on boot/release; OPcache on with `validate_timestamps=0`; `APP_DEBUG=false`; logs to `stderr`.
- **DEP-05** Behind a proxy: `trustProxies` configured, `APP_URL` https, secure cookies; TLS terminated at one place (Dokploy/Traefik or NPM — not both on the same hop by accident).
- **DEP-06** Secrets only in the platform's env/secret store; `.env` never baked into images.
- **DEP-07** Health checks: `/up` for liveness, spatie/laravel-health for readiness; containers restart on failure.
- **DEP-08** Workers restart on deploy (`horizon:terminate` / container replace) so they run new code; scheduler single instance (`onOneServer`).
- **DEP-09** Backups automated, off-site, monitored and restore-tested (SPA-06); persistent volumes for DB, Redis (AOF), storage/MinIO.
- **DEP-10** CI gates every deploy: Pint, Larastan, Pest (+browser), type check, build, `composer audit`/`npm audit`.
- **DEP-11** Rollback path documented: previous image tag + compatible migrations.
- **DEP-12** Monitoring in place before launch: error tracking, uptime, queue health, disk, logs.

## References

| Topic | File |
|---|---|
| Docker image & compose (serversideup/php, FrankenPHP) | `references/docker.md` + `assets/docker/` |
| Dokploy | `references/dokploy.md` |
| Nginx Proxy Manager / Traefik / plain Nginx | `references/proxies.md` |
| Forge, Laravel Cloud, Ploi, shared hosting | `references/other-hosts.md` |
| CI/CD (GitHub Actions) | `references/ci-cd.md` + `assets/.github/workflows/` |
| Go-live checklist | `references/go-live-checklist.md` |
