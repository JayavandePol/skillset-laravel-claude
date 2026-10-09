# Legacy: Laravel Breeze & Jetstream

**Status:** With Laravel 12 (Feb 2025) the new starter kits replaced Breeze and Jetstream; neither receives new features. Existing apps keep working — the code was published into the app — but don't start new projects on them. Verify the current status on the Laravel docs/GitHub before advising.

## Recognising them

- Breeze: `laravel/breeze` in `require-dev`, `app/Http/Controllers/Auth/*` controllers, `routes/auth.php`, Blade/Livewire Volt or Inertia (React/Vue) views.
- Jetstream: `laravel/jetstream` in `require`, Fortify + Sanctum, `app/Actions/Jetstream/*`, Teams (`Team`, `Membership`, `TeamInvitation`), Livewire or Inertia stack.

## Maintaining a Breeze app

- Breeze's controllers are plain app code: apply SEC/AUTH rules directly (rate limits in `LoginRequest`, `Password::defaults()`, email verification).
- `laravel/breeze` itself can be removed from `composer.json` after install — it's only the scaffolding command. Check nothing imports it first.
- Adding 2FA: install Fortify's two-factor feature only, or use a package; or migrate to a new kit.

## Maintaining a Jetstream app

- Jetstream is a runtime dependency (Teams, API tokens UI, profile). Keep it updated to the latest release supporting your Laravel version; check `composer why-not laravel/framework 13.*` before upgrading Laravel.
- Teams: Jetstream's own roles (`Jetstream::role()`) — if you also need fine-grained permissions, layer `spatie/laravel-permission` with teams keyed on `team_id` rather than replacing Jetstream's roles abruptly.
- Livewire stack: Jetstream v5 targets Livewire 3. For Livewire 4 upgrades, check Jetstream compatibility first.

## Migrating to the new starter kits

There's no automatic migration. Approach:

1. Create a fresh starter-kit app with the same frontend stack.
2. Copy over your domain code (models, migrations, actions, pages) and tests.
3. Port Jetstream Teams to your own `workspaces` + `workspace_user` tables (keep table names/ids to avoid data migration, or write a migration that renames).
4. Keep user passwords (same hashing) — users don't need to reset.
5. Run the full test suite + browser checks; switch over.

Only do this when there's a reason (needs Livewire 4/Inertia v3 features, abandoned dependency blocks upgrades). Otherwise maintain in place.
