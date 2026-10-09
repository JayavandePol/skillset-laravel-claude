# Other hosting targets

## Laravel Forge (+ Envoyer-style zero downtime)

- Provisions Nginx, PHP, DB, Redis, Supervisor on your VPS (Hetzner, DO, AWS…).
- Deploy script (zero-downtime enabled):

  ```bash
  $CREATE_RELEASE()
  cd $FORGE_RELEASE_DIRECTORY
  $FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
  npm ci && npm run build
  $FORGE_PHP artisan migrate --force
  $FORGE_PHP artisan optimize
  $ACTIVATE_RELEASE()
  $FORGE_PHP artisan horizon:terminate
  ```

- Daemons: Horizon, Reverb, SSR; Scheduler: enable in Forge.
- Env via Forge UI; SSL via Let's Encrypt.

## Laravel Cloud

Fully managed (autoscaling, managed Postgres/MySQL, Valkey, object storage, queue workers, scheduler). Configure build/deploy commands in the dashboard; environment variables per environment; hibernation for previews. Check EU region availability for EU clients.

## Ploi

Similar to Forge (server management + zero-downtime deploys); Docker support available.

## Shared hosting (cPanel/Plesk)

Possible for small apps, with limits:
- Document root must point to `public/` (or a subdomain set to it). Never expose the project root.
- No long-running workers: use `QUEUE_CONNECTION=database` with a cron `* * * * * php artisan schedule:run` and `Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping()`.
- No SSR, no Reverb; build assets locally/CI and upload `public/build`.
- PHP version must be ≥ 8.3 for Laravel 13.
- Prefer moving to a VPS/Dokploy once the app needs queues, websockets or scaling.
