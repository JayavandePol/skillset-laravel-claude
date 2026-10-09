# Stack decision matrix

Present the relevant rows to the user in Phase 0 with a recommendation; record choices in `docs/progress.md`.

## Frontend

| Option | Choose when | Avoid when |
|---|---|---|
| **Inertia + React** | app-like SaaS, rich interactions (kanban, editors, drag & drop), team knows React, large component ecosystem (shadcn) | team is PHP-only; mostly CRUD forms |
| **Inertia + Vue** | same as React but team prefers Vue | — |
| **Inertia + Svelte** | team prefers Svelte; smaller ecosystem acceptable | need many ready-made components |
| **Livewire 4 + Flux** | CRUD-heavy, dashboards, internal tools, PHP-only team, fast delivery | very rich client-side interactions, offline needs |
| **Blade only** | marketing sites, simple content | interactive apps |
| **API-only** (+ separate SPA/mobile) | mobile apps, multiple clients, public API product | single web client (Inertia is simpler) |
| **Filament as the whole app** | internal tools, ERP/CRM for employees, admin-first products | consumer-facing UX with custom branding |

## Auth

| Option | Choose when |
|---|---|
| Starter kit (Fortify) | default for any app with UI |
| Starter kit + WorkOS | enterprise SSO, social logins, passkeys without building them |
| Fortify headless | custom UI not based on a starter kit |
| Sanctum tokens | mobile/third-party API access |
| Passport | you are an OAuth2 provider |
| Socialite | "Login with Google/Microsoft" alongside local accounts |
| Breeze / Jetstream | only for existing apps (legacy) |

## Tenancy

| Option | Choose when |
|---|---|
| None | single-organisation app |
| Shared DB + `tenant_id` (default) | B2B SaaS, workspaces/teams |
| DB per tenant (`stancl/tenancy`) | strict isolation, per-tenant backups/restore, regulated data |

Tenant identification: path (`/w/{slug}`) default; subdomain when branding per tenant matters; custom domains later.

## Database

PostgreSQL (default) · MariaDB/MySQL (hosting/team constraint) · SQLite (small/single-server, local dev). See `laravel-database/references/postgres-vs-mariadb.md`.

## Admin

Filament v5 (default when needed) · custom Inertia/Livewire admin (when admin UX must match the app closely) · none.

## Payments

Stripe via Cashier (international SaaS) · Paddle (merchant of record, VAT handled) · Mollie (NL/BE: iDEAL, Bancontact) · none.

## Realtime

Reverb (self-hosted, default) · Pusher/Ably (managed) · polling (simple, low frequency).

## Search

Scout database driver (start) → Meilisearch/Typesense (typo tolerance, facets) → pgvector + AI SDK (semantic).

## Hosting

Dokploy on a VPS (self-hosted, Docker) · Nginx Proxy Manager + Docker Compose · Laravel Cloud (managed) · Forge/Ploi (managed VPS) · shared hosting (small apps only, no workers/websockets).
