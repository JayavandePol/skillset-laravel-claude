# Delivery phases in detail

## Phase 0 — Blueprint & teardown
- Research protocol (`research-protocol.md`), then the blueprint template (`blueprint.md`).
- Decision matrix rows relevant to the project (`decision-matrix.md`).
- Output saved to `docs/blueprint.md`; ask for approval with open decisions listed.

## Phase 1 — Foundation & architecture
1. Scaffold: `laravel new <app>` with the chosen starter kit (or existing project analysis).
2. Tooling from `laravel-standards/assets`: `pint.json`, `phpstan.neon`, `rector.php`, `tests/ArchTest.php`, composer scripts, `.editorconfig`, `CLAUDE.md`.
3. `AppServiceProvider::boot()`: model strictness, `Password::defaults()`, rate limiters (`laravel-security/assets/rate-limiters.php`), `Date::use(CarbonImmutable::class)`, `Vite::useCspNonce()` if CSP.
4. Middleware pipeline in `bootstrap/app.php`: Inertia/Livewire, SecurityHeaders, CSP, tenant middleware alias, `trustProxies`.
5. Routing layout: `web.php` (pages, tenant-prefixed groups with `scopeBindings()`), `api.php` only for external consumers, `channels.php`, `console.php` schedule.
6. Exception rendering: domain exceptions, Inertia error pages / API error format.
7. Auth configured (2FA, verification, rate limits) per `laravel-auth`.
8. CI pipeline (`laravel-deployment/assets/.github/workflows/ci.yml`) green on the empty app.

## Phase 2 — Database
- Migrations per ERD with FK strategies and indexes (DB-01…07), models with casts/relations/scopes, factories with states, idempotent seeders (reference + demo), tenancy trait and middleware.
- Tests: factories create valid graphs; tenancy scope test.

## Phase 3 — Frontend / API
- Inertia: layouts, shared data, typed props, Wayfinder, pages per epic, error pages, SSR decision.
- Livewire: layouts, components/pages per epic, Flux.
- API: versioned routes, resources, error format, docs.

## Phase 4 — Validation & security
- FormRequests for all writes, policies for all models, tenant-scoped rules, headers + CSP (report-only first), rate limits per endpoint group, upload rules, signed URLs, privacy features.
- Walk `laravel-security/references/owasp-checklist.md` for touched areas.

## Phase 5 — Packages
- Filament panel (if chosen), Spatie Permission (teams) + seeding, Media Library collections/conversions, activity log, backup + health, Horizon, Reverb, Scout, Pennant, billing — only what the blueprint requires.

## Phase 6 — Verification (per epic, continuously)
- Pest feature/Inertia/Livewire/API tests incl. authorization matrix; browser tests for primary workflows; smoke test over main pages.
- Boot the app and run the browser checklist (`laravel-testing/references/browser-verification.md`); record results.
- Run `laravel-audit` scanner before each milestone; fix errors.

## Phase 7 — Performance & deployment
- Queue all slow work; caching strategy; query performance pass (EXPLAIN on top queries); bundle size check.
- Docker image + compose (`laravel-deployment/assets/docker`), Dokploy/NPM setup, secrets, backups, monitoring.
- Go-live checklist (`laravel-deployment/references/go-live-checklist.md`).

## Milestone report template (end of each phase/epic)

```markdown
## <Phase/Epic> — done
- Built: <bullets with file links>
- Decisions: <ADR one-liners>
- Verification: tests <N passed>, phpstan <clean/level>, audit <errors/warnings>, browser <checked pages>
- Open: <questions / follow-ups>
- Next: <phase/epic>
```
