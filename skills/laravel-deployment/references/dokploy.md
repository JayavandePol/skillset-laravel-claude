# Dokploy

Self-hosted PaaS on Docker + Traefik. Check https://docs.dokploy.com for current UI names.

## Option A — Compose application (recommended for Laravel)

1. Create project → **Compose** service → source: Git repo, compose path `docker-compose.prod.yml` (or `assets/docker` template adapted).
2. Use a pre-built image from CI (`APP_IMAGE=ghcr.io/org/app:<sha>`) or let Dokploy build from the Dockerfile.
3. **Environment** tab: all `.env` values (APP_KEY, DB_*, REDIS_*, MAIL_*, AWS_*, REVERB_*). Dokploy writes them to an `.env` file next to the compose file — `env_file: .env` in compose picks them up.
4. **Domains** tab: attach `app.example.com` → service `app`, port `8080`, HTTPS with Let's Encrypt. Reverb: `ws.example.com` → `reverb:8080`.
5. Network: services that receive traffic must join `dokploy-network` (external). Set `PROXY_NETWORK=dokploy-network` for the template.
6. **Deployments**: enable auto-deploy webhook; trigger from CI after tests pass (GitHub Actions → Dokploy API/webhook).
7. Volumes: named volumes for postgres/redis/minio; Dokploy's **Backups** for databases (to S3) and/or spatie/laravel-backup.

## Option B — Separate applications

Dokploy "Application" per role from the same image (web, worker, scheduler) + Dokploy-managed Postgres/Redis services. More clicks, but each role is visible/restartable separately.

## Tips

- Don't put Nginx Proxy Manager in front of Dokploy's Traefik for the same domain unless you deliberately chain (then disable TLS on one hop and forward `X-Forwarded-*`).
- `trustProxies(at: '*')` (Traefik is on the private network) so Laravel sees HTTPS.
- Zero-downtime: Dokploy can do rolling/health-checked deploys for applications; with compose, keep migrations backwards-compatible since containers restart.
- Logs: Dokploy shows container stdout — `LOG_CHANNEL=stderr`.
- Scheduled tasks: the `scheduler` container (not Dokploy cron) keeps everything in code.
