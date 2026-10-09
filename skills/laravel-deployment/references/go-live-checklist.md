# Go-live checklist

## Code & build
- [ ] CI green on the release commit (quality, tests, browser tests, audits).
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` set (and backed up securely), `APP_URL` https.
- [ ] `LOG_CHANNEL=stderr` (containers) / daily files with rotation (VPS).
- [ ] `php artisan optimize` runs on release; OPcache enabled.
- [ ] No dev packages in the image (Telescope, Debugbar, ide-helper).

## Data
- [ ] Migrations reviewed; backward compatible; run once with `--force`.
- [ ] Reference data seeded (roles, permissions, plans); no demo seeders.
- [ ] Backups scheduled, off-site, encrypted, monitored — **restore tested**.

## Processes
- [ ] Queue workers (Horizon) running, restart on deploy, failed-job alerting.
- [ ] Scheduler running once; schedule monitored.
- [ ] SSR / Reverb containers healthy (if used).

## Security
- [ ] `laravel-security/references/owasp-checklist.md` completed.
- [ ] Security headers + CSP enforced; HSTS.
- [ ] Admin panels and dashboards gated (+ IP restrict optional).
- [ ] Rate limits active; `curl https://site/.env` → 404.

## Infra
- [ ] TLS valid, auto-renew; HTTP→HTTPS redirect; `trustProxies` set.
- [ ] DNS: A/AAAA, mail SPF/DKIM/DMARC; transactional mail tested.
- [ ] Storage: S3 bucket private, CORS correct, lifecycle rules.
- [ ] Health endpoint wired to uptime monitoring.

## Observability
- [ ] Error tracking DSN set, release tagging, PII scrubbing.
- [ ] Pulse/Nightwatch (or equivalent) dashboards.
- [ ] Alerts: errors spike, queue backlog, disk > 80%, backup failure, certificate expiry.

## Product
- [ ] Legal pages (privacy, terms, cookie statement), DPA template for B2B.
- [ ] Accessibility pass (WCAG 2.2 AA) on core flows.
- [ ] 404/500 pages branded; maintenance mode page (`php artisan down --render=errors::503 --secret=…`).

## Rollback
- [ ] Previous image tag known; procedure written in `docs/runbook.md`.
