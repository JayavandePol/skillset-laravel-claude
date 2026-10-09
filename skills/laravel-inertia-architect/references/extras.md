# Recommended Extras

Things the original brief didn't list but most production SaaS apps need. Propose the relevant ones in Phase 0 (as MVP / later) instead of adding them silently.

## Product & UX
- **Realtime collaboration** — Laravel Reverb (self-hosted WebSockets) + `@laravel/echo-react` hooks; private channels per workspace/board, presence channels for "who's viewing". Authorize in `routes/channels.php` using the same policies.
- **Notifications** — database + mail channels, per-user preferences, digest emails via the scheduler; Filament database notifications for admins.
- **Activity feed / audit log** — `spatie/laravel-activitylog`; doubles as the card/deal timeline.
- **Global search & command palette** — Laravel Scout (database driver first, Meilisearch/Typesense later) + shadcn `Command` (⌘K).
- **Onboarding** — demo workspace/template on signup, empty states that teach.
- **i18n** — Dutch + English at minimum for NL/BE clients: `lang/*.json` + a React i18n helper (e.g. `laravel-react-i18n`), locale in shared props, `Carbon::setLocale`.
- **Feature flags** — Laravel Pennant, scoped per workspace; gate plan features and roll out gradually.
- **Billing** — Laravel Cashier (Stripe) or Mollie (popular in NL/BE: iDEAL, Bancontact) with plan limits enforced in policies.
- **Public API & webhooks** — Sanctum tokens per workspace, JSON:API resources (first-class in Laravel 13), outgoing webhooks with `spatie/laravel-webhook-server`.
- **AI features** — Laravel 13's first-party AI SDK (summaries, smart search with embeddings + pgvector). Keep provider keys server-side and queue the calls.

## Engineering
- **CI pipeline** — GitHub Actions (`assets/.github/workflows/ci.yml`): Pint, Larastan, Pest (parallel), browser tests, `npm run types`, `npm run build`, `composer audit`, `npm audit`; build & push Docker image on `main`.
- **Static analysis & refactoring** — Larastan (level 6+), Rector with Laravel rules, Pint, ESLint + Prettier, TypeScript strict.
- **Laravel Boost / MCP** — install `laravel/boost` in the project so AI agents get version-correct docs and app introspection (routes, schema, logs). Strongly recommended for agent-driven development.
- **Error tracking & observability** — Sentry or Flare, Laravel Pulse/Nightwatch, structured JSON logs to stdout.
- **Backups** — `spatie/laravel-backup` to off-site S3, monitored, restore tested quarterly.
- **Seed/demo data** — rich factories so every page has realistic data in dev and screenshots.
- **Docs for future sessions** — `docs/blueprint.md`, `docs/progress.md`, a short `CLAUDE.md` in the project with commands, conventions and "don'ts".

## Compliance (EU / NL)
- **GDPR/AVG** — data export (per user/workspace), account deletion with grace period, data processing register, DPA with sub-processors, cookie consent only if non-essential cookies are used.
- **Data residency** — EU hosting and EU S3 region.
- **Accessibility** — WCAG 2.2 AA (European Accessibility Act applies to many B2C services since June 2025).
- **E-invoicing** — Peppol/UBL for Belgian B2B invoices (mandatory since 2026) if building an invoicing product.
