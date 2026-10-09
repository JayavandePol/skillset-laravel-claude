---
name: laravel-audit
description: Audit an existing Laravel project against the full laravel-* skill library — runs a bundled static scanner (strict types, env() misuse, debug calls, mass assignment, raw SQL injection, XSS sinks, unlocked Livewire properties, missing policies, missing FK constraints, committed secrets, missing tooling/tests/CI), then a manual checklist for what tools can't see, and produces a prioritised report with rule IDs and fixes. Use this whenever the user asks to audit, review, assess, health-check or "check everything" in a Laravel codebase, takes over or inherits a project, prepares for launch or a pentest, or asks "is this project up to standard / secure / clean?".
---

# Laravel Audit

Purpose: tell the user, with evidence, how far a project is from the library's rules and what to fix first. Audit first, fix after agreement — an audit that silently rewrites half the codebase is not what anyone asked for.

## Step 1 — Context

Read `composer.json`, `package.json`, `bootstrap/app.php`, `config/` (auth, session, permission, filesystems), `routes/`, project `CLAUDE.md`. Note Laravel/PHP versions, stack (Inertia/Livewire/API/Filament), tenancy model, key packages.

## Step 2 — Automated scan

```bash
php <skill-dir>/scripts/audit.php <project-path>                # Markdown report
php <skill-dir>/scripts/audit.php <project-path> --format=json  # for further processing
php <skill-dir>/scripts/audit.php <project-path> --min=warning  # hide info
```

`<skill-dir>` is this skill's directory (inside the plugin: `${CLAUDE_PLUGIN_ROOT}/skills/laravel-audit`). The script is read-only, dependency-free, and exits 1 when errors are found (usable in CI).

Then run the project's own tools if installed and report their real output:

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
php artisan test --parallel
composer audit
npm audit --omit=dev
php artisan route:list --except-vendor   # for the authorization review
```

The scanner is heuristic: **verify every finding** by reading the code before reporting it as a problem; drop false positives and say you did.

## Step 3 — Manual review (what regexes can't see)

Work through these with the relevant skill open:

1. **Authorization** (`laravel-security`): every route from `route:list` → is it authorized (policy/gate/FormRequest)? Cross-tenant tests present? `scopeBindings()`?
2. **Validation**: write endpoints have FormRequests with complete rules; tenant-scoped `exists`.
3. **Architecture** (`laravel-standards`): business logic location, fat controllers/components, duplicated logic between controllers/Livewire/Filament.
4. **Database** (`laravel-database`): N+1 on main pages (enable strict mode locally and click through), missing indexes for main list queries, money as floats, enum strings.
5. **Frontend** (`laravel-inertia`/`laravel-livewire`): props leaking columns, unbounded lists, missing error/empty states.
6. **Auth** (`laravel-auth`): 2FA for admins, rate limits, password policy, legacy Breeze/Jetstream status.
7. **Ops** (`laravel-deployment`): queues/scheduler/backups/monitoring in production, `APP_DEBUG`, proxies.
8. **Privacy**: personal data, encrypted casts, export/delete flows.

## Step 4 — Report

Use this structure (write to `docs/audit-<date>.md` in the project when the user wants a file):

```markdown
# Audit — <project> (<date>)
Stack: Laravel x.y, PHP x.y, <frontend>, <db>, <key packages>

## Summary
- Overall: <one paragraph>
- Critical: N · High: N · Medium: N · Low: N
- Top 3 actions

## Findings
| # | Severity | Rule | Location | Issue | Impact | Fix |
(Critical = exploitable security / data loss; High = security weakness or production failure risk;
 Medium = maintainability/performance debt; Low = style/tooling)

## Tooling results
pint / phpstan / tests / audits — real output summary

## Fix plan
1. Quick wins (≤1h each)
2. Security fixes
3. Structural refactors (by epic, with tests first)
4. Tooling to add (pint.json, phpstan, ArchTest, CI) — templates in laravel-standards/assets
```

## Step 5 — Fix (only after the user agrees)

- Fix in priority order, one logical change per commit, tests first for behaviour changes.
- Add the missing enforcement (Pint config, Larastan, ArchTest, CI with `composer check` and the audit script) so the same issues can't come back.
- Re-run the scanner and tools; report before/after counts.
