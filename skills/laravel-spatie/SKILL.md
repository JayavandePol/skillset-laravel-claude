---
name: laravel-spatie
description: Select, install and configure Spatie's Laravel packages correctly — laravel-permission (roles/permissions incl. teams), laravel-medialibrary, laravel-activitylog, laravel-backup, laravel-health, laravel-data + typescript-transformer, laravel-query-builder, laravel-csp, laravel-honeypot, laravel-translatable, laravel-sluggable, laravel-tags, laravel-settings, laravel-model-states, laravel-webhook-client/server, laravel-pdf, laravel-responsecache, laravel-passkeys and more. Use this whenever the user mentions any Spatie package, needs roles & permissions, file/media uploads with conversions, audit logs, backups, health checks, DTOs, API filtering, CSP, spam protection, translations of model fields, slugs, tags, settings, state machines, webhooks, or PDFs in Laravel.
---

# Laravel + Spatie packages

Spatie packages are the de-facto standard for many cross-cutting concerns. Install only what the project needs, configure them deliberately, and keep them updated.

## Before installing any package

1. Check the package docs (https://spatie.be/docs/<package>) for the major version that supports the project's Laravel/PHP version — Spatie often releases a new major per Laravel major.
2. `composer require spatie/<package>` (let Composer pick a compatible version; pin `^X.Y` in `composer.json`).
3. Publish config/migrations only if you'll change them; read the published config fully.
4. Record the package and why in the project `CLAUDE.md`.

## Catalog — when to use what

| Need | Package | Reference |
|---|---|---|
| Roles & permissions (optionally per team/workspace) | `laravel-permission` | `references/permission.md` |
| Uploads, images, conversions, responsive images | `laravel-medialibrary` | `references/medialibrary.md` |
| Audit trail / activity feed | `laravel-activitylog` | `references/activitylog.md` |
| Backups of DB + files, monitoring | `laravel-backup` | `references/backup-health.md` |
| Health checks endpoint | `laravel-health` | `references/backup-health.md` |
| DTOs, validation, TS types | `laravel-data`, `laravel-typescript-transformer` | `references/data-typescript.md` |
| API filter/sort/include from query string | `laravel-query-builder` | `references/query-builder.md` |
| CSP headers | `laravel-csp` | `laravel-security/references/headers-csp.md` |
| Spam protection on forms | `laravel-honeypot` | `references/misc.md` |
| Translatable model fields (JSON) | `laravel-translatable` | `references/content.md` |
| Slugs | `laravel-sluggable` | `references/content.md` |
| Tags | `laravel-tags` | `references/content.md` |
| Typed app settings stored in DB | `laravel-settings` | `references/content.md` |
| State machines | `laravel-model-states` | `references/misc.md` |
| Receive / send webhooks | `laravel-webhook-client`, `laravel-webhook-server` | `references/misc.md` |
| PDFs from Blade/HTML | `laravel-pdf` | `references/misc.md` |
| Full-response caching for public pages | `laravel-responsecache` | `references/misc.md` |
| Passkeys | `laravel-passkeys` (or first-party Fortify passkeys) | `laravel-auth` |
| Image optimisation | `laravel-image-optimizer` | `references/medialibrary.md` |
| GDPR data export | `laravel-personal-data-export` | `laravel-security/references/privacy.md` |
| Schedule monitoring | `laravel-schedule-monitor` | `references/backup-health.md` |

## Rules

- **SPA-01** Permissions are checked by **permission name** (enum-backed), not role name; roles are seeded bundles.
- **SPA-02** With teams enabled, the team id is set in every context (HTTP middleware, jobs, commands, broadcast auth, tests) before permission checks.
- **SPA-03** Permission cache is cleared after seeding/changes (`PermissionRegistrar::forgetCachedPermissions()`); never query roles in loops.
- **SPA-04** Media conversions are queued; collections declare accepted MIME types and disks; files live on private disks unless genuinely public.
- **SPA-05** Activity log excludes sensitive attributes, logs only dirty fields, and is pruned (`activitylog:clean`).
- **SPA-06** Backups run daily to an off-site disk, are monitored (`backup:monitor`), notify on failure, and a restore is tested.
- **SPA-07** Health checks cover DB, cache, queue/Horizon, scheduler heartbeat, disk space, and are wired to the container healthcheck/uptime monitor.
- **SPA-08** Query builder: every filter/sort/include explicitly allowlisted; never `allowedFilters` from user-provided lists.
- **SPA-09** Webhook clients verify signatures and process via queue; webhook servers sign payloads and retry.
