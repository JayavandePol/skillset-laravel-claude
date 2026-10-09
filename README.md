# skillset-laravel-claude

A Claude Code skill (packaged as a plugin) that turns Claude into a **Lead Full-Stack Laravel Architect + Principal Product Manager**. It plans, builds and verifies complete **Laravel 13 + Inertia.js (React) + Tailwind** apps with **Filament** and the **Spatie** ecosystem, including "remakes" of existing SaaS products (Trello, Teamleader, …).

## What it does

Claude works in gated phases and checks the current official docs before planning:

| Phase | Output |
|---|---|
| 0. Blueprint & teardown | Researches the target SaaS, then writes epics, user stories, a Mermaid ERD, a tech stack map and open decisions. **Waits for your approval.** |
| 1. Architecture & routing | Actions / Services / query objects, route and middleware design, quality gates |
| 2. Database | Migrations with indexes and FK strategies, Eloquent models, tenancy scope, N+1 prevention |
| 3. Frontend | React + Inertia v3 structure, typed props (Wayfinder), state strategy, partial reloads, deferred props, SSR |
| 4. Validation & security | Form Requests, React Hook Form + Zod bridged to Inertia errors, Policies, CSP, rate limiting |
| 5. Packages | Filament v5 admin panel, Spatie Permission (teams = workspaces), Spatie Media Library |
| 6. UI verification | Boot commands, browser checklist (console, network, React DevTools), Pest browser tests / Dusk |
| 7. Performance & deploy | Queues + Horizon, Docker image, Dokploy / Nginx Proxy Manager, deploy checklist |
| + Extras | Realtime (Reverb), audit log, search, i18n (NL/EN), billing (Mollie/Stripe), CI, backups, GDPR |

Progress is stored in the target project (`docs/blueprint.md`, `docs/progress.md`) so work can continue across sessions.

## Install

### As a plugin (recommended)

In Claude Code:

```
/plugin marketplace add JayavandePol/skillset-laravel-claude
/plugin install laravel-inertia-architect@skillset-laravel-claude
```

### As a plain skill

Copy the skill folder into your personal skills directory:

```bash
git clone https://github.com/JayavandePol/skillset-laravel-claude.git
cp -r skillset-laravel-claude/skills/laravel-inertia-architect ~/.claude/skills/
```

(On Windows: `%USERPROFILE%\.claude\skills\`.) Or copy it into a project's `.claude/skills/` to share it with your team through git.

## Usage

The skill triggers automatically on Laravel/Inertia project requests, or call it directly:

```
/laravel-inertia-architect Build a Trello remake for our agency, Dutch + English UI
```

Example prompts:

- "Make a Teamleader-like CRM with quotations and invoices for Belgian clients."
- "Plan the database for a booking SaaS like Calendly in Laravel."
- "Add a Filament admin panel and workspace roles to this Laravel React app."
- "Prepare this Laravel app for deployment on Dokploy."

## Structure

```
.claude-plugin/                 plugin + marketplace manifests
skills/laravel-inertia-architect/
├── SKILL.md                    workflow, phase gating, defaults
├── references/                 one file per phase + research protocol + extras
└── assets/                     Dockerfile, compose, Action, CSP policy, browser test, CI workflow
```

## Notes

Version numbers in the references were checked in October 2026. The skill tells Claude to re-verify against the official docs every time, and the installed versions in a project always take precedence.

## License

MIT
