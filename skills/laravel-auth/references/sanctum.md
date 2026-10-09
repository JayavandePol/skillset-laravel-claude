# Laravel Sanctum

```bash
php artisan install:api      # installs Sanctum, publishes migration, creates routes/api.php
```

## Mode 1 — SPA cookie authentication

For a separate SPA on the **same top-level domain** (app.example.com + api.example.com). (Inertia apps don't need this — they use normal session auth.)

```dotenv
SANCTUM_STATEFUL_DOMAINS=app.example.com
SESSION_DOMAIN=.example.com
```

```php
// bootstrap/app.php
->withMiddleware(fn (Middleware $m) => $m->statefulApi())
```

Flow: `GET /sanctum/csrf-cookie` → `POST /login` → requests with cookies. CORS: `supports_credentials => true`, explicit origins.

## Mode 2 — API tokens (mobile, integrations)

```php
$token = $user->createToken(
    name: $request->validated('name'),
    abilities: ['invoices:read', 'invoices:write'],
    expiresAt: now()->addDays(90),
);

return ['token' => $token->plainTextToken]; // show once
```

```php
Route::middleware(['auth:sanctum', 'abilities:invoices:write'])->post('/invoices', ...);
// or in code: $request->user()->tokenCan('invoices:write')
```

- `config/sanctum.php` → `'expiration' => 60 * 24 * 90` (minutes) as a global ceiling.
- Prune: `Schedule::command('sanctum:prune-expired --hours=24')->daily();`
- Tenant-bound tokens: store `tenant_id` on the token (custom `PersonalAccessToken` model via `Sanctum::usePersonalAccessTokenModel()`) and set tenant context from it in middleware.
- Token UI: list name, abilities, last used, expiry; revoke button; requires `password.confirm`.

## Testing

```php
Sanctum::actingAs($user, ['invoices:read']);
$this->getJson('/api/v1/invoices')->assertOk();
```
