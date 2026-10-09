---
name: laravel-security
description: Non-negotiable security rules for every Laravel app — validation with FormRequests, authorization (Policies/Gates), mass assignment, IDOR and tenant isolation, XSS, CSRF, SQL injection, file uploads, secrets, CSP and security headers, rate limiting, session/cookie hardening, auth hardening (2FA, passkeys), webhooks, dependency audits and GDPR/AVG basics. Use this whenever code touches user input, authentication, authorization, uploads, raw queries, HTML rendering, cookies, APIs, webhooks, payments or personal data — and whenever the user mentions security, OWASP, pentest findings, hardening, "is this safe", or production readiness in a Laravel project.
---

# Laravel Security

Security rules are not style preferences — breaking one is a bug, even if the feature "works". Apply them while writing code, not as an afterthought. When a rule can't be met, stop and tell the user why instead of silently weakening it.

The server is the only trust boundary: every rule is enforced server-side, whatever the frontend does.

## Rules

### Input & validation — `references/validation.md`
- **SEC-01** Every write endpoint (web, API, Livewire action, Filament custom action) validates input with a **FormRequest** or explicit `validate()` — explicit types, lengths, formats, enums, array limits.
- **SEC-02** Only `$request->validated()` / `safe()` / a DTO reaches Actions and models. Never `$request->all()`, `$request->input()` without validation, or `$request->except()` into `create()`/`update()`/`fill()`.
- **SEC-03** `exists`/`unique` rules are scoped to the current tenant/owner.
- **SEC-04** Models declare `$fillable` (or the L13 attribute equivalent); never `Model::unguard()` globally, never `$guarded = []` on models that receive request data.

### Authorization — `references/authorization.md`
- **SEC-10** Every route that reads or writes a resource is authorized: policy (`authorize()`/`can` middleware/FormRequest `authorize()`), not just "logged in".
- **SEC-11** A Policy exists for every model exposed to users; tests cover allowed, denied and **cross-tenant** cases.
- **SEC-12** Child route bindings use `scopeBindings()`; IDs in payloads are checked against the tenant. Public URLs use ULIDs/UUIDs, not sequential IDs, where enumeration matters.
- **SEC-13** Livewire: public properties are user-editable — lock them (`#[Locked]`) or re-authorize in every action. Filament: resources have policies; panels implement `canAccessPanel()`.
- **SEC-14** UI-hidden ≠ authorized. Permission flags shared to the frontend are cosmetic; the server re-checks.

### Output & injection — `references/injection-xss.md`
- **SEC-20** Blade uses `{{ }}`. `{!! !!}`, `dangerouslySetInnerHTML`, `v-html` only for content sanitised server-side with an allowlist sanitizer (HTML Purifier / symfony/html-sanitizer).
- **SEC-21** No user input in raw SQL fragments: `whereRaw/selectRaw/orderByRaw/DB::raw/DB::statement` use bindings; dynamic column/sort names are allowlisted.
- **SEC-22** User-supplied URLs are validated for scheme (`https`/`http` only) before use in `href`, redirects (no open redirects — use `redirect()->intended()` with internal URLs or allowlisted hosts) or server-side fetches (SSRF: block private IP ranges).
- **SEC-23** No `eval`, `unserialize` on user data, `shell_exec`/`exec`/`Process` with user input (use argument arrays + `escapeshellarg` if unavoidable), dynamic `include`.

### Sessions, cookies, CSRF — `references/headers-csp.md`
- **SEC-30** CSRF protection on all web routes; exclusions only for signed webhooks.
- **SEC-31** Production: `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax` (or `strict`), `SESSION_ENCRYPT=true` for database/redis sessions; regenerate session on login (framework default — don't break it).
- **SEC-32** Security headers on every response: HSTS, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, frame protection; CSP with nonces (start report-only, then enforce). Middleware template in `assets/SecurityHeaders.php`.

### Authentication — `references/auth-hardening.md`
- **SEC-40** Passwords: `Password::defaults()` → min 12, mixed case/numbers in production, `uncompromised()`. Hashing via `Hash`/framework defaults (bcrypt/argon2id); never custom crypto.
- **SEC-41** Login, register, password reset, 2FA, invite and verification endpoints are rate limited per email+IP.
- **SEC-42** 2FA available (Fortify) and enforced for admins; passkeys offered where the stack supports them.
- **SEC-43** Admin panels (Filament, Horizon, Telescope, Pulse) are gated to explicit roles; Telescope never enabled in production unless locked down.
- **SEC-44** Signed URLs (`URL::temporarySignedRoute`) for email links, downloads and unsubscribe; verify with the `signed` middleware.

### Files — `references/uploads.md`
- **SEC-50** Uploads validated by real MIME/extension allowlist and max size (`File::types()->max()`); images re-encoded when served publicly.
- **SEC-51** User uploads stored on a non-public disk with generated names; served via authorized controller or temporary signed URLs. Never in `public/` with the original filename.
- **SEC-52** No SVG/HTML uploads rendered inline from your domain unless sanitised (stored XSS).

### Secrets & config
- **SEC-60** `APP_DEBUG=false` and `APP_ENV=production` in production; debug tooling not installed in production images.
- **SEC-61** Secrets only in env/secret stores, never in git, logs, exceptions, frontend props, or error messages.
- **SEC-62** `composer audit` and `npm audit` run in CI; known-vulnerable versions block the merge.

### Rate limiting & abuse
- **SEC-70** Named rate limiters for API, auth, uploads, invites, search, exports and anything that sends email/SMS or costs money (`RateLimiter::for` in a provider).
- **SEC-71** Public forms have bot protection (`spatie/laravel-honeypot` or Turnstile) in addition to rate limits.

### Integrations
- **SEC-80** Incoming webhooks verify signatures (Stripe, Mollie, GitHub…) and are idempotent; use `spatie/laravel-webhook-client` for storage + verification.
- **SEC-81** Outgoing HTTP has timeouts and doesn't follow redirects to internal networks when URLs are user-supplied.

### Privacy (GDPR/AVG) — `references/privacy.md`
- **SEC-90** Personal data minimised; sensitive fields encrypted at rest with `encrypted` casts (IBAN, BSN, tokens, notes with health data).
- **SEC-91** Users can export and delete their data; deletions cascade or anonymise; backups have a retention policy.
- **SEC-92** Logs and error trackers scrub PII; third-party processors listed.

## How to apply

- **Writing code:** check the relevant rules before finishing any change that touches the areas above. Mention in your summary which SEC rules applied.
- **Reviewing/auditing:** run `laravel-audit` (script + checklist) or the `laravel-security-auditor` agent; report findings with rule IDs, file:line, impact and fix.
- **Pre-launch:** walk `references/owasp-checklist.md` end to end.

Templates: `assets/SecurityHeaders.php` (headers middleware), `assets/CspPolicy.php` (spatie/laravel-csp preset), `assets/rate-limiters.php` (provider snippet).
