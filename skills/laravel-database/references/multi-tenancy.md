# Multi-tenancy (DB-30, DB-31)

## Choose a model

| Model | When | Tooling |
|---|---|---|
| **Single DB, `tenant_id` column** (default) | B2B SaaS, workspaces/teams, most apps | own `BelongsToTenant` trait, Spatie permission teams |
| Single DB, PostgreSQL schemas | stronger isolation, still one server | `stancl/tenancy` or custom |
| **Database per tenant** | regulatory isolation, per-tenant restore, very large tenants | `stancl/tenancy` (check current major), `spatie/laravel-multitenancy` |

Also decide: tenant from **URL path** (`/w/{workspace:slug}`), **subdomain** (`acme.app.com`), or **session** (current workspace switcher). Path is simplest and works with one TLS cert.

## Shared-DB implementation

```php
// app/Support/CurrentTenant.php — registered as scoped (Octane-safe)
final class CurrentTenant
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void { $this->tenant = $tenant; }
    public function get(): ?Tenant { return $this->tenant; }
    public function id(): ?int { return $this->tenant?->id; }
}

// AppServiceProvider::register
$this->app->scoped(CurrentTenant::class);
```

```php
// app/Models/Concerns/BelongsToTenant.php
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $tenantId = app(CurrentTenant::class)->id();
            if ($tenantId !== null) {
                $query->where($query->qualifyColumn('tenant_id'), $tenantId);
            }
        });

        static::creating(function (Model $model): void {
            $model->tenant_id ??= app(CurrentTenant::class)->id()
                ?? throw new LogicException('No tenant context when creating '.$model::class);
        });
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

```php
// Middleware: SetCurrentTenant
public function handle(Request $request, Closure $next): Response
{
    $tenant = $request->route('workspace');
    abort_unless($request->user()?->belongsToTenant($tenant->id), 404);

    app(CurrentTenant::class)->set($tenant);
    setPermissionsTeamId($tenant->id);          // spatie/laravel-permission teams
    Context::add('tenant_id', $tenant->id);     // logs & jobs

    return $next($request);
}
```

## Contexts that skip HTTP middleware

- **Queued jobs:** pass the tenant id; set `CurrentTenant` + `setPermissionsTeamId()` at the start of `handle()` (or a job middleware). Laravel's `Context` is propagated to jobs automatically — read it back in a job middleware.
- **Console commands / scheduler:** loop tenants explicitly.
- **Broadcast channel auth:** check membership in `routes/channels.php`.
- **Filament admin:** `withoutGlobalScope('tenant')` deliberately; or Filament's own tenancy for tenant-facing panels.

## Three-layer enforcement (DB-31)

1. Global scope (safety net).
2. Policies + `scopeBindings()` (primary).
3. Tenant-scoped validation (`Rule::exists(...)->where('tenant_id', …)`).

And tests: every resource has a cross-tenant access test returning 404.
