# Sessions, CSRF, headers & CSP (SEC-30 … SEC-32)

## CSRF

- `web` middleware group includes CSRF validation; Inertia/Axios/Livewire send the token automatically (`XSRF-TOKEN` cookie → `X-XSRF-TOKEN` header, or `_token`).
- Exclusions in `bootstrap/app.php`:

  ```php
  $middleware->validateCsrfTokens(except: ['webhooks/mollie', 'webhooks/stripe']);
  ```

  Only for endpoints that verify a signature instead.
- Handle 419 (expired page) with a friendly reload instead of a raw error.

## Session & cookie settings (production .env)

```dotenv
SESSION_DRIVER=redis          # or database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=null           # set only when sharing across subdomains deliberately
```

Use `__Host-` prefixed cookie names for extra protection when no subdomain sharing is needed (`SESSION_COOKIE=__Host-app_session` with `SESSION_DOMAIN=null` and secure on).

## Security headers

Middleware template `assets/SecurityHeaders.php`, appended to the `web` group. Headers:

| Header | Value |
|---|---|
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` (add `preload` only when sure) |
| `X-Content-Type-Options` | `nosniff` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=(), payment=()` (open what you use) |
| `X-Frame-Options` | `DENY` (or CSP `frame-ancestors 'none'`) |
| `Cross-Origin-Opener-Policy` | `same-origin` |

Headers can also be set at Nginx/Traefik; pick one place and document it.

## Content Security Policy

`spatie/laravel-csp` (`composer require spatie/laravel-csp`, publish config, add middleware) with a preset like `assets/CspPolicy.php`:

- `default-src 'self'`; `script-src 'self' 'nonce-…'` — Vite assets get the nonce via `Vite::useCspNonce()`.
- `style-src 'self' 'unsafe-inline'` is usually needed for component libraries (Radix, Filament, Livewire) — tighten when possible.
- `img-src 'self' data: blob: <s3/cdn host>`; `connect-src 'self' wss://<reverb host>`; `frame-ancestors 'none'`; `form-action 'self'`; `base-uri 'self'`; `object-src 'none'`.
- Local dev: allow the Vite dev server (`http://localhost:5173`, `ws://localhost:5173`).
- Livewire/Alpine: Alpine's standard build needs `'unsafe-eval'`; use the Alpine CSP build if you want to avoid it — check Livewire's CSP docs for the installed version.
- Filament panels may need their own relaxed policy — apply CSP per route group if necessary.
- Roll out: report-only → watch reports for a week → enforce.

## CORS

Only for APIs consumed from other origins: `config/cors.php` with explicit `allowed_origins`, never `*` together with credentials.
