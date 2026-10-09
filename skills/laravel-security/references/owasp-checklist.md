# Pre-launch security checklist (OWASP Top 10:2021 + ASVS highlights)

Walk this before every production launch and after large features. Record results in `docs/security.md`.

## A01 Broken access control
- [ ] Every route listed (`php artisan route:list --except-vendor`) has auth + policy/gate, or is intentionally public.
- [ ] Cross-tenant tests exist for every resource.
- [ ] No sequential IDs in public URLs where enumeration leaks data.
- [ ] Admin tools (Filament, Horizon, Pulse, Telescope, Log viewer) gated.
- [ ] Livewire locked properties / re-authorization in actions.

## A02 Cryptographic failures
- [ ] HTTPS everywhere, HSTS on, secure cookies.
- [ ] Sensitive columns use `encrypted` casts; backups encrypted.
- [ ] No custom crypto; `APP_KEY` set and secret.

## A03 Injection
- [ ] `grep -rn "Raw(\|DB::raw\|DB::statement\|DB::unprepared" app/` — every hit uses bindings/allowlists.
- [ ] No `{!! !!}` / `dangerouslySetInnerHTML` / `v-html` on unsanitised data.
- [ ] No shell execution with user input.

## A04 Insecure design
- [ ] Rate limits on auth, invites, exports, AI calls, emails, SMS.
- [ ] Business limits enforced server-side (plan limits, quantities, prices from DB not from client).
- [ ] Payment amounts computed server-side; webhooks verified and idempotent.

## A05 Security misconfiguration
- [ ] `APP_DEBUG=false`, `APP_ENV=production`, no Telescope/Debugbar in production.
- [ ] Security headers + CSP enforced.
- [ ] Directory listing off; only `public/` is web root; `.env` not reachable (test `curl https://site/.env` → 404).
- [ ] Default credentials removed (seeders don't run in production).

## A06 Vulnerable components
- [ ] `composer audit` and `npm audit --omit=dev` clean (or accepted with reason).
- [ ] Dependabot/Renovate enabled.
- [ ] Laravel/PHP on supported versions.

## A07 Identification & authentication failures
- [ ] Password policy, rate limits, 2FA for admins, password confirmation for sensitive actions.
- [ ] Session regenerated on login; logout invalidates session.
- [ ] Password reset tokens expire (default 60 min) and are single use.

## A08 Software & data integrity
- [ ] CI builds images; production pulls signed/tagged images, not `latest` from a laptop.
- [ ] No `unserialize` on untrusted data; signed URLs for sensitive links.

## A09 Logging & monitoring
- [ ] Auth events, permission changes, admin actions logged (activity log).
- [ ] Error tracking + uptime monitoring + alerting on failed jobs.
- [ ] Logs don't contain secrets/PII.

## A10 SSRF
- [ ] Any server-side fetch of user-provided URLs validates scheme + blocks private ranges.

## Extra
- [ ] File uploads: validation, private storage, no inline SVG/HTML.
- [ ] CORS restricted.
- [ ] GDPR: export/delete flows, retention pruning, processor list.
- [ ] Backups restore-tested.
