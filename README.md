# skillset-laravel-claude

A complete **Laravel skill library for Claude Code**, packaged as one plugin. Install it once and every Laravel project gets the same architecture, coding standards, security rules, testing protocol and deployment setup, with every rule numbered so reviews and audits can cite it.

It works for new projects (including "remakes" of existing SaaS products such as Trello or Teamleader) and existing ones (audit → fix plan → enforcement).

## What's inside

### Skills

| Skill | What it covers | Rule IDs |
|---|---|---|
| `laravel-architect` | **Entry point.** Architect + PM workflow: research → blueprint (epics, ERD, stack map) → **approval** → phased, vertical delivery. SaaS teardown protocol, stack decision matrix, extras. | — |
| `laravel-standards` | Clean code & architecture: Actions, Services, DTOs, enums, thin controllers, config/env, naming, errors/logging, tooling (Pint, Larastan, Rector, Pest arch tests) | STD-* |
| `laravel-security` | Validation, authorization, tenant isolation, XSS/SQLi/SSRF, CSRF & cookies, headers/CSP, auth hardening, uploads, rate limits, webhooks, GDPR/AVG, OWASP checklist | SEC-* |
| `laravel-database` | Schema/ERD, migrations with FK strategies & indexes, zero-downtime changes, Eloquent, multi-tenancy, N+1, factories/seeders, PostgreSQL vs MariaDB | DB-* |
| `laravel-auth` | Starter kits (React/Vue/Svelte/Livewire, WorkOS), Fortify, 2FA/passkeys, **Breeze & Jetstream (legacy)**, Sanctum, Passport, Socialite, invitations/teams | AUTH-* |
| `laravel-inertia` | Inertia v3 with React 19 / Vue 3 / Svelte 5: structure, typed props, Wayfinder, state, partial/deferred props, forms (useForm, RHF + Zod, Precognition), optimistic UI, realtime, SSR | FE-* |
| `laravel-livewire` | Livewire 4 + Flux: single-file components, islands, secure public properties, forms, testing, upgrades | LW-* |
| `laravel-api` | Versioned REST/JSON:API, resources, error format, Sanctum abilities, pagination/filters, idempotency, webhooks, Scramble docs | API-* |
| `laravel-filament` | **Optional** Filament v5 admin panels: access control, resources, actions, tenancy, Spatie plugins, tests | FIL-* |
| `laravel-spatie` | Permission (teams), Media Library, Activitylog, Backup, Health, Data + TypeScript, Query Builder, CSP, Honeypot, Translatable, Sluggable, Tags, Settings, Model States, Webhooks, PDF… | SPA-* |
| `laravel-ecosystem` | Queues/Horizon, scheduler, Reverb, Scout, Pennant, Cashier/Paddle/**Mollie**, mail & notifications, Octane, Pulse, Telescope, Nightwatch, Herd/Sail, **Boost**, Laravel 13 AI SDK | ECO-* |
| `laravel-testing` | Pest 4+ (unit, feature, arch, browser), Dusk, Inertia/Livewire/Filament/API tests, fakes, boot commands, manual browser verification checklist | TST-* |
| `laravel-deployment` | Docker (serversideup/php), compose roles, **Dokploy**, **Nginx Proxy Manager**, Traefik, Forge, Laravel Cloud, shared hosting, CI/CD, go-live checklist | DEP-* |
| `commit` | Commits (and pushes) in **your** personal style: no Co-Authored-By, no trailers, no AI mentions. Learns your style from your git history once, stores it in `~/.claude/commit-style.md` (per-repo override: `.claude/commit-style.md`), and updates it whenever you say "from now on…" | — |
| `laravel-audit` | Audits an existing project: bundled **static scanner** (`scripts/audit.php`) + manual checklist → prioritised report with rule IDs → fix plan | — |

### Agents

- **`laravel-reviewer`** reviews a diff, branch or PR against the rules, runs Pint/Larastan/tests, and returns a findings table and a verdict.
- **`laravel-security-auditor`** runs a read-only security audit: a route authorization matrix, tenant isolation, injection and XSS sinks, auth hardening, and an OWASP status.

### Hook

- **Pint on save:** after Claude edits a `.php` file in a Laravel project, the project's own `vendor/bin/pint` formats it. It does nothing outside Laravel projects, when Pint isn't installed, or when `php` isn't on the hook shell's PATH. Turn it off with `LARAVEL_SKILLS_NO_PINT=1`.

### Templates (assets)

`pint.json`, `phpstan.neon` (level 8), `rector.php`, `tests/ArchTest.php` (rules as tests), composer `check` scripts, `.editorconfig`, a project `CLAUDE.md` template, a `SecurityHeaders` middleware, a CSP preset, rate limiters, a Dockerfile, `docker-compose.prod.yml` (web, worker, scheduler, SSR, Reverb, Postgres, Valkey, MinIO), a GitHub Actions CI workflow, and Pest browser smoke and workflow tests.

## Install

### As a plugin (recommended)

In Claude Code:

```
/plugin marketplace add JayavandePol/skillset-laravel-claude
/plugin install laravel-skillset@skillset-laravel-claude
```

### As plain skills

```bash
git clone https://github.com/JayavandePol/skillset-laravel-claude.git
cp -r skillset-laravel-claude/skills/* ~/.claude/skills/
```

(On Windows: `%USERPROFILE%\.claude\skills\`.) You can also copy them into a project's `.claude/skills/` to share them with your team through git. Agents and the hook only come with the plugin install.

## Usage

The skills trigger automatically based on what you ask. Typical starts:

```
/laravel-architect Build a Trello remake for our agency, Dutch + English UI, self-hosted on Dokploy
/laravel-architect Teamleader-like CRM with quotations and invoices for Belgian clients, Livewire + Filament
/laravel-audit Audit this project and give me a fix plan
```

Other examples:

- "Add workspace roles with Spatie Permission teams to this app."
- "Should I use Breeze, Jetstream or the new starter kit for this?"
- "Make an API for our mobile app with Sanctum tokens."
- "Prepare this app for production on Dokploy behind Nginx Proxy Manager."
- "Review my changes before I merge." (uses the `laravel-reviewer` agent)
- "Commit this." / "Learn my commit style." / "From now on, write commit subjects in Dutch." (uses `commit`)

### Audit script in CI

```bash
php path/to/skills/laravel-audit/scripts/audit.php . --min=warning
```

It exits with code 1 when it finds errors, so it can block a merge.

## Pairs well with Laravel Boost

Install [`laravel/boost`](https://laravel.com/docs/boost) in each project (`composer require laravel/boost --dev && php artisan boost:install`). Boost gives Claude version-matched docs and app introspection over MCP. This library adds your rules, protocols and enforcement on top. If they disagree on an API detail, the installed version's docs win. If they disagree on policy (security, structure), the library wins.

## Versions

The references were checked in October 2026 against Laravel 13, Inertia v3, Livewire 4, Filament v5, Pest 4, Spatie Permission v7/v8 and Media Library 11.21+. Every skill tells Claude to check the official docs again before it uses an API, and the versions installed in a project always take precedence.

## License

MIT
