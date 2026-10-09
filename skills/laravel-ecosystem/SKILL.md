---
name: laravel-ecosystem
description: Use Laravel's first-party packages correctly — queues & Horizon, task scheduling, Reverb + Echo broadcasting, Scout search (database, Meilisearch, Typesense), Pennant feature flags, Cashier billing (Stripe, Paddle) and Mollie for NL/BE, mail & notifications, Octane, Pulse, Telescope, Nightwatch, Pail, Precognition, Sail, Herd, Laravel Boost (AI guidelines + MCP) and the Laravel 13 AI SDK. Use this whenever the user adds background jobs, realtime/websockets, search, feature flags, subscriptions/payments, emails/notifications, performance tooling, monitoring, AI features, or asks which Laravel package to use for something.
---

# Laravel first-party ecosystem

Prefer first-party packages: they track framework releases, share conventions, and are documented at laravel.com/docs. Check the docs page for each package before installing — install commands and config change between majors.

## Catalog

| Need | Package | Reference |
|---|---|---|
| Background jobs, retries, batches; queue dashboard | Queues + **Horizon** (Redis) | `references/queues-scheduling.md` |
| Scheduled tasks | Scheduler (`routes/console.php`) | `references/queues-scheduling.md` |
| Realtime, websockets, presence | **Reverb** + Echo | `references/broadcasting.md` |
| Full-text search | **Scout** (database → Meilisearch/Typesense) | `references/search.md` |
| Feature flags, gradual rollout, plan gating | **Pennant** | `references/pennant.md` |
| Subscriptions & payments | **Cashier** (Stripe/Paddle), Mollie | `references/billing.md` |
| Email, SMS, Slack, in-app notifications | Mail + Notifications | `references/mail-notifications.md` |
| High-throughput app server | **Octane** (FrankenPHP/Swoole/RoadRunner) | `references/performance-observability.md` |
| Production metrics | **Pulse** (self-hosted) / **Nightwatch** (hosted) | `references/performance-observability.md` |
| Local debugging | **Telescope**, Debugbar, **Pail** | `references/performance-observability.md` |
| Live validation | **Precognition** | `laravel-inertia/references/forms.md` |
| Local environment | **Herd** (macOS/Windows), **Sail** (Docker) | `references/local-dev.md` |
| AI agent guidelines + MCP for your project | **Boost** | `references/ai.md` |
| LLM features (text, embeddings, images) | Laravel 13 **AI SDK** | `references/ai.md` |
| Typed routes in TS | **Wayfinder** | `laravel-inertia` |

## Rules

- **ECO-01** Anything slow (>~300ms), external, or retryable runs on a queue; jobs are idempotent with `$tries`, `$backoff`, `$timeout`, `failed()`, and `afterCommit`.
- **ECO-02** Production queues use Redis/Valkey + Horizon (or SQS/database with a supervisor) — never `sync`. Separate queues by priority.
- **ECO-03** Scheduled tasks use `->onOneServer()` + `->withoutOverlapping()` when running on multiple instances; scheduler runs as its own process (`schedule:work`) or cron.
- **ECO-04** Broadcast channels are private/presence with authorization callbacks; payloads minimal (ids), no sensitive data.
- **ECO-05** Search indexes contain only fields needed for search + tenant id; queries always filter by tenant.
- **ECO-06** Feature flags (Pennant) scoped to the right entity (tenant, user); flag checks on the server; stale flags removed.
- **ECO-07** Payment state is driven by verified webhooks, not redirects; amounts calculated server-side; prices/plans in config or DB, never from the client.
- **ECO-08** Mail/notifications are queued, use Markdown mailables or components, have plain-text alternatives, are localized, and respect user preferences/unsubscribe.
- **ECO-09** Telescope/Debugbar never active in production; Pulse/Horizon dashboards gated (SEC-43).
- **ECO-10** Octane only after auditing for request-state leakage (static props, singletons holding request/user/tenant → use `scoped`).
- **ECO-11** AI calls are queued or streamed, rate limited (SEC-70), keys server-side, user data minimized, outputs treated as untrusted (escape/sanitise, never execute).
