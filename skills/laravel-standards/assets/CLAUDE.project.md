# <Project name>

<!-- Template from the laravel-standards skill. Copy to the project root as CLAUDE.md and fill in. Keep it short. -->

## Stack
- Laravel <13.x>, PHP <8.4>, <PostgreSQL 17 / MariaDB 11>
- Frontend: <Inertia v3 + React 19 + TS + Tailwind v4 | Livewire 4 + Flux | API only>
- Admin: <Filament v5 at /admin | none>
- Auth: <starter kit + Fortify | WorkOS | Sanctum API tokens>
- Key packages: <spatie/laravel-permission (teams), spatie/laravel-medialibrary, horizon, reverb …>

## Commands
```bash
composer run dev        # app + queue + logs + vite
composer check          # pint --test, phpstan, pest
php artisan test --filter=<name>
npm run types && npm run lint
```

## Conventions
- Follows the laravel-* skill library rules (STD/SEC/DB/…); deviations listed below.
- Business logic in `app/Actions`, reads in `app/Queries`, DTOs in `app/Data`.
- Tenancy: <every tenant-owned table has `workspace_id`; `BelongsToWorkspace` global scope>.
- Public IDs: <ULID column `ulid`, route key>.

## Deviations from the library (with reason)
- <none>

## Don'ts
- Don't edit migrations that have run in production.
- Don't call `env()` outside `config/`.
- Don't add packages without noting them here.

## Docs
- Blueprint: `docs/blueprint.md` · Progress & decisions: `docs/progress.md`
