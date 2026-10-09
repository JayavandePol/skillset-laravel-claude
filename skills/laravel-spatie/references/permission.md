# spatie/laravel-permission

Versions: v7 supports Laravel 12–13 (PHP 8.3+); v8 adds enum support in `findByName()`/`findOrCreate()` — check https://spatie.be/docs/laravel-permission for the current major and its prerequisites.

## Install

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

**Decide on teams before migrating.** For workspace/tenant-scoped roles, set in `config/permission.php`:

```php
'teams' => true,
'team_foreign_key' => 'workspace_id',   // or tenant_id / team_id
```

Then `php artisan migrate`. Add `use HasRoles;` to `User`.

## Enums

```php
enum Permission: string
{
    case InvoicesView = 'invoices.view';
    case InvoicesCreate = 'invoices.create';
    case InvoicesUpdate = 'invoices.update';
    case InvoicesDelete = 'invoices.delete';
    case MembersManage = 'members.manage';
    case BillingManage = 'billing.manage';
}

enum Role: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    /** @return list<Permission> */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => Permission::cases(),
            self::Admin => array_values(array_filter(Permission::cases(), fn (Permission $p) => $p !== Permission::BillingManage)),
            self::Member => [Permission::InvoicesView, Permission::InvoicesCreate, Permission::InvoicesUpdate],
            self::Viewer => [Permission::InvoicesView],
        };
    }
}
```

## Seeder (idempotent, production-safe)

```php
final class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            SpatiePermission::findOrCreate($permission->value, 'web');
        }
    }
}
```

With teams, roles are usually created **per team** when the team is created (or as global roles with `team_id = null` — check the "Teams permissions" docs for how global roles behave in your version):

```php
// inside CreateWorkspace action
setPermissionsTeamId($workspace->id);
foreach (Role::cases() as $role) {
    $model = SpatieRole::findOrCreate($role->value, 'web'); // team-scoped because team id is set
    $model->syncPermissions(array_map(fn (Permission $p) => $p->value, $role->permissions()));
}
$owner->assignRole(Role::Owner->value);
```

## Setting the team id (SPA-02)

```php
// HTTP middleware (after resolving tenant)
setPermissionsTeamId($tenant->id);

// switching tenant in the same process (jobs, tests, commands)
setPermissionsTeamId($otherTenant->id);
$user->unsetRelation('roles')->unsetRelation('permissions');
```

Job middleware example:

```php
final class SetTenantContext
{
    public function __construct(private int $tenantId) {}

    public function handle(object $job, Closure $next): void
    {
        app(CurrentTenant::class)->set(Tenant::findOrFail($this->tenantId));
        setPermissionsTeamId($this->tenantId);
        $next($job);
    }
}
```

## Checking

```php
$user->can(Permission::InvoicesUpdate->value);          // via Gate integration
$user->hasPermissionTo(Permission::InvoicesUpdate);     // enums accepted in recent versions
Route::middleware('permission:invoices.update');        // route middleware (register aliases if needed)
@can('invoices.update') … @endcan
```

Use these inside **Policies** (SEC-11) so model state and tenant checks are combined.

## Super admin

Platform staff: boolean `is_super_admin` checked in `Gate::before()` **only for the admin panel/guard**, or explicit checks — don't give tenant users global bypasses.

## Testing

```php
beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets members update invoices but not delete them', function () {
    [$user, $tenant] = userWithRole(Role::Member);
    setPermissionsTeamId($tenant->id);

    expect($user->can('invoices.update'))->toBeTrue()
        ->and($user->can('invoices.delete'))->toBeFalse();
});
```
