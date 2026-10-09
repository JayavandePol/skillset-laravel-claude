# Configuration & environment

## env() only in config (STD-20)

```php
// config/services.php
'mollie' => [
    'key' => env('MOLLIE_KEY'),
    'webhook_secret' => env('MOLLIE_WEBHOOK_SECRET'),
],

// app code
$key = config('services.mollie.key');
```

After `php artisan config:cache`, `env()` returns `null` outside config files — the bug only shows in production. The audit script flags it.

## Custom config files

Create `config/<app-or-domain>.php` for app settings (limits, defaults, feature switches) instead of constants scattered in code:

```php
return [
    'boards' => [
        'max_per_workspace' => (int) env('BOARDS_MAX_PER_WORKSPACE', 100),
    ],
    'invites' => [
        'expire_after_days' => 7,
    ],
];
```

Cast env values (`(int)`, `(bool)` via `env()`'s own parsing of `true/false`), and give safe defaults.

## .env.example (STD-21)

- Every key the app reads, with placeholder values, grouped and commented.
- Never real credentials. Use `APP_KEY=` empty.
- Production-only settings documented in a comment block (`SESSION_SECURE_COOKIE=true`, `LOG_CHANNEL=stderr`).

## Secrets

- Development: `.env` (gitignored).
- Production: platform secrets (Dokploy env, Forge, Docker secrets, Laravel Cloud) — or `php artisan env:encrypt` with the key stored outside the repo.
- Rotate `APP_KEY` with `APP_PREVIOUS_KEYS` so encrypted data and sessions survive.
- Check git history for leaked `.env` files when taking over a project (`git log --all -- .env`).

## Environment-specific behaviour

- Branch on `app()->isProduction()` / `app()->environment('local')`, never on `APP_URL` or hostnames.
- Local-only packages (`telescope`, `debugbar`, `ide-helper`) in `require-dev` and registered conditionally.

## Caching for production (STD-23)

```bash
php artisan optimize        # config, routes, events, views
php artisan optimize:clear  # when debugging
```

Route closures are allowed in `routes/console.php` and for trivial `Route::view()`, but real endpoints use controllers.
