# Phase 5 — Packages & Ecosystem

## Filament v5 (internal admin)

Filament runs on Livewire 4 and is completely separate from the Inertia app — different routes, layout and CSS. It's for staff/super-admins, not customers.

```bash
composer require filament/filament:"^5.0"
php artisan filament:install --panels      # creates app/Providers/Filament/AdminPanelProvider.php
php artisan make:filament-user             # local only
php artisan make:filament-resource Workspace --generate --view
php artisan make:filament-theme            # only if you customise styling (Tailwind v4)
```

Panel provider essentials:

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->login()
        ->colors(['primary' => Color::Indigo])
        ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
        ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
        ->databaseNotifications()
        ->spa()
        ->middleware([/* panel defaults */])
        ->authMiddleware([Authenticate::class]);
}
```

Access control — `User` implements `FilamentUser`:

```php
public function canAccessPanel(Panel $panel): bool
{
    return $panel->getId() === 'admin' && $this->is_super_admin && $this->hasVerifiedEmail();
}
```

Guidelines:

- Resources call the same **Actions** as the Inertia controllers (e.g. a "Suspend workspace" table action calls `SuspendWorkspace::handle()`), so business rules live in one place.
- Policies are respected automatically by Filament resources.
- Tenant global scopes: in admin you see all workspaces — call `->modifyQueryUsing(fn ($q) => $q->withoutGlobalScope('workspace'))` on the resource's table query (or override `getEloquentQuery()`).
- Useful additions: dashboard widgets (MRR, signups, active workspaces), impersonation (`stechstudio/filament-impersonate` — check v5 compatibility), activity log viewer, Horizon/Pulse links in navigation.
- Check each third-party Filament plugin for v5 support before adding it.
- Give Filament its own Vite theme input; the Inertia `app.css` must not be loaded on `/admin`.

## Spatie Laravel Permission (workspace roles)

Check v7 vs v8 for the project's Laravel version first.

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Before migrating, enable teams in `config/permission.php`:

```php
'teams' => true,
'team_foreign_key' => 'workspace_id',
```

Then `php artisan migrate`. Model: `use HasRoles;` on `User`.

Set the team context per request (inside `SetCurrentWorkspace` middleware):

```php
setPermissionsTeamId($workspace->id);
$request->user()->unsetRelation('roles')->unsetRelation('permissions'); // when switching workspace in one request/job
```

Also set it in queued jobs and broadcast channel callbacks that check permissions — they don't run the HTTP middleware.

Define permissions and roles as enums and seed them idempotently:

```php
enum Permission: string
{
    case BoardsCreate = 'boards.create';
    case BoardsDelete = 'boards.delete';
    case CardsUpdate = 'cards.update';
    case MembersManage = 'members.manage';
    case BillingManage = 'billing.manage';
}

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Guest = 'guest';

    /** @return list<Permission> */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => Permission::cases(),
            self::Admin => array_values(array_filter(Permission::cases(), fn ($p) => $p !== Permission::BillingManage)),
            self::Member => [Permission::BoardsCreate, Permission::CardsUpdate],
            self::Guest => [],
        };
    }
}
```

```php
// RolesAndPermissionsSeeder (global permissions, roles created per team or with team_id null for global role templates)
app()[PermissionRegistrar::class]->forgetCachedPermissions();
foreach (Permission::cases() as $p) {
    \Spatie\Permission\Models\Permission::findOrCreate($p->value, 'web');
}
```

When a workspace is created (in `CreateWorkspace` action): set the team id, create/assign the `owner` role to the creator. Check the "Teams permissions" docs page for how roles with `team_id = null` (global) behave in the installed version.

Notes:
- Permissions are cached; call `forgetCachedPermissions()` after seeding and in tests' `setUp` if needed.
- Use `$user->can('cards.update')` / policies in code; keep role names out of business logic (check permissions, not roles).
- Super-admin for Filament is a boolean column or a separate global role, not a workspace role.

## Spatie Media Library (attachments, avatars, covers)

```bash
composer require spatie/laravel-medialibrary:"^11.21"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate
composer require filament/spatie-laravel-media-library-plugin:"^5.0"  # if editing media in Filament
```

```php
final class Card extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk('s3')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'text/plain']);

        $this->addMediaCollection('cover')->singleFile()->useDisk('s3');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)->height(240)->format('webp')
            ->performOnCollections('attachments', 'cover')
            ->queued();
    }
}
```

Upload through an Action:

```php
$card->addMediaFromRequest('file')
    ->usingFileName(Str::uuid().'.'.$request->file('file')->extension())
    ->withCustomProperties(['uploaded_by' => $user->id])
    ->toMediaCollection('attachments');
```

- Validate in the FormRequest (`File::types([...])->max(20 * 1024)`) — collection mime rules are a second layer.
- Private disks: serve with `$media->getTemporaryUrl(now()->addMinutes(10))`.
- Self-hosted S3: MinIO container; set `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT=true`.
- Conversions need a queue worker and image tooling (GD/Imagick, `jpegoptim`, `pngquant`… — included in the Dockerfile asset).
- React upload: Inertia `useForm` with a `File` value sends multipart automatically and exposes `progress`.

## Other Spatie packages worth considering

| Package | Use |
|---|---|
| `spatie/laravel-data` | DTOs + validation + TS generation |
| `spatie/laravel-typescript-transformer` | PHP enums/DTOs → TS types |
| `spatie/laravel-activitylog` | audit trail / card activity feed |
| `spatie/laravel-backup` | DB + storage backups to S3 |
| `spatie/laravel-csp` | Content Security Policy |
| `spatie/laravel-sluggable` | slugs for workspaces/boards |
| `spatie/laravel-query-builder` | safe filter/sort/include from query string (allowlisted) |
| `spatie/laravel-health` | health checks endpoint for Docker/Dokploy |
| `spatie/laravel-pdf` | invoice/quotation PDFs (Teamleader-style apps) |
