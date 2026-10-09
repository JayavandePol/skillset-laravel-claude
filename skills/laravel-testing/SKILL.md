---
name: laravel-testing
description: Testing and verification protocol for Laravel apps — Pest 4+ (unit, feature, architecture, browser testing with Playwright), Laravel Dusk for legacy suites, Inertia assertInertia tests, Livewire and Filament component tests, API tests, fakes (Queue, Mail, Notification, Http, Storage, Event), factories, parallel runs, coverage and mutation testing, plus a manual browser verification checklist (console, network tab, Inertia JSON, React/Vue DevTools, responsive, a11y) and exact commands to boot the environment. Use this whenever you write or run tests, finish a feature and need to verify it works, set up CI testing, debug failing tests, or the user asks "does it work?", "test this", "check it in the browser" in a Laravel project.
---

# Laravel Testing & Verification

A feature is done when it is proven to work: automated tests for the logic and contract, and a real browser check for anything with UI. Report actual results — failing output included — never "should work".

## Rules

- **TST-01** Every Action, Policy, FormRequest rule-set and endpoint has tests; every bug fix starts with a failing test.
- **TST-02** Each guarded route has allowed / denied / unauthenticated / **cross-tenant** tests (SEC-11).
- **TST-03** Inertia pages: `assertInertia` on component + key props (+ `missing` for fields that must not leak). Livewire/Filament: component tests for each action.
- **TST-04** 1–3 browser tests per epic covering primary workflows, plus a smoke test (`assertNoSmoke`/no JS errors) over main pages.
- **TST-05** External services are faked (`Http::fake()`, service interface fakes, `Queue::fake()`, `Mail::fake()`, `Storage::fake()`); `Http::preventStrayRequests()` globally in tests.
- **TST-06** Tests are independent: `RefreshDatabase` (or `LazilyRefreshDatabase`), factories not seeders, no order dependence; run in parallel.
- **TST-07** Arch tests encode the standards (`laravel-standards/assets/tests/ArchTest.php`).
- **TST-08** CI runs the full suite against the production DB engine; the suite stays fast (< ~5 min) — slow tests are fixed, not skipped.
- **TST-09** Coverage tracked for domain code (`--coverage --min=80` on `app/Actions`, `app/Policies` is a good target); mutation testing (`--mutate`) on critical domains (billing, permissions).
- **TST-10** Before saying a UI feature is done: boot the app and run the browser checklist (`references/browser-verification.md`), using the available browser tool if you have one.

## References

| Topic | File |
|---|---|
| Pest setup, structure, helpers, datasets, fakes | `references/pest.md` |
| Feature/HTTP, Inertia, Livewire, Filament, API test patterns | `references/feature-tests.md` |
| Browser testing (Pest browser plugin, Dusk) | `references/browser-tests.md` |
| Boot commands + manual browser verification checklist | `references/browser-verification.md` |

Templates: `assets/tests/Browser/SmokeTest.php`, `assets/tests/Browser/KanbanBoardTest.php` (workflow example); `tests/Pest.php` setup is in `references/pest.md`.

## Commands

```bash
php artisan test --parallel                  # or ./vendor/bin/pest --parallel
./vendor/bin/pest --filter="invoices"         # subset
./vendor/bin/pest tests/Browser               # browser tests
./vendor/bin/pest --coverage --min=80
./vendor/bin/pest --mutate --path=app/Actions/Billing
php artisan dusk                              # if the project uses Dusk
```
