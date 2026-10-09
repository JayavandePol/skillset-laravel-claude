---
name: laravel-filament
description: Optional admin panels and back-offices with Filament v5 (Livewire 4, Tailwind 4) — when to use it, installing panels, resources, tables, forms, actions, widgets, panel access control, policies, multi-tenancy in panels, Spatie Media Library and Permission integrations, plugins, theming, performance and testing. Use this whenever the user wants an admin panel, back-office, internal dashboard, CRUD management screens, or mentions Filament, Filament resources, Filament tables/forms/actions/widgets, or upgrading Filament v3/v4 to v5.
---

# Laravel Filament

Filament is **optional**. Use it when the project needs internal CRUD/back-office screens fast; skip it for apps whose only users are customers using the main UI. Decide in the blueprint and record the decision.

| Situation | Recommendation |
|---|---|
| SaaS with Inertia/Livewire customer app + staff admin | Filament panel at `/admin` for staff only |
| Internal tool / CRM / ERP for a company's employees | Filament *is* the app (panel at `/`), possibly with tenancy |
| Customer-facing app where customers manage data in tables/forms | Filament tenant panel possible, but weigh branding/UX control vs. a custom UI |
| API-only or tiny app | No Filament |

Filament v5 = Filament v4 features + Livewire 4 support. Requirements: PHP 8.2+, Laravel 11.28+, Tailwind 4. Check https://filamentphp.com/docs for the installed version before using any API.

## Rules

- **FIL-01** Panel access is explicit: `User implements FilamentUser` with `canAccessPanel()` checking a super-admin flag/permission + verified email; 2FA enforced for admin panels (Filament's MFA or Fortify).
- **FIL-02** Every resource model has a Policy; Filament uses it automatically — never rely on hiding navigation.
- **FIL-03** Resources/actions call the app's **Actions** for business operations (STD-01); Filament forms don't duplicate domain logic.
- **FIL-04** Custom actions validate their form data and authorize (`->authorize(...)`/`->visible(fn () => auth()->user()->can(...))` plus server check).
- **FIL-05** Tables eager load (`->modifyQueryUsing(fn ($q) => $q->with([...]))`), use `->counts()`/`->sums()`, paginate, and index searchable/sortable columns.
- **FIL-06** Tenant global scopes are bypassed deliberately in staff panels (`getEloquentQuery()` without the scope) and enforced in tenant panels (Filament tenancy).
- **FIL-07** Destructive bulk actions require confirmation and are queued for large sets.
- **FIL-08** Third-party plugins checked for v5 compatibility and maintenance before adding.
- **FIL-09** Filament assets/theme are separate from the customer app's CSS; panel CSP adjusted if needed.
- **FIL-10** Resources tested with Filament's Livewire testing helpers (list, create, edit, delete, authorization).

## Install

```bash
composer require filament/filament:"^5.0"
php artisan filament:install --panels            # AdminPanelProvider at app/Providers/Filament
php artisan make:filament-user                    # local only
php artisan make:filament-resource Customer --generate --view --soft-deletes
php artisan make:filament-theme                   # only when customising styles
```

## Panel provider

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->login()
        ->passwordReset()
        ->emailVerification()
        ->multiFactorAuthentication([AppAuthentication::make()])   // v4+/v5 MFA — check docs
        ->colors(['primary' => Color::Indigo])
        ->brandName(config('app.name').' Admin')
        ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
        ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
        ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
        ->databaseNotifications()
        ->spa()
        ->unsavedChangesAlerts()
        ->authMiddleware([Authenticate::class]);
}
```

```php
// App\Models\User
public function canAccessPanel(Panel $panel): bool
{
    return match ($panel->getId()) {
        'admin' => $this->is_super_admin && $this->hasVerifiedEmail(),
        default => false,
    };
}
```

## Resource essentials

```php
public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(fn (Builder $q) => $q->with('customer:id,name'))
        ->columns([
            TextColumn::make('number')->searchable()->sortable(),
            TextColumn::make('customer.name')->searchable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('total_cents')->money('EUR', divideBy: 100)->sortable(),
            TextColumn::make('lines_count')->counts('lines'),
        ])
        ->filters([SelectFilter::make('status')->options(InvoiceStatus::class), TrashedFilter::make()])
        ->recordActions([
            Action::make('send')
                ->requiresConfirmation()
                ->authorize('send')
                ->action(fn (Invoice $record, SendInvoice $send) => $send->handle(auth()->user(), $record)),
            EditAction::make(),
        ])
        ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
        ->defaultSort('issued_on', 'desc');
}
```

(Namespaces/method names moved between v3 → v4/v5, e.g. `actions()` → `recordActions()`; check the docs for the installed version.)

## Integrations

- **Spatie Media Library:** `composer require filament/spatie-laravel-media-library-plugin:"^5.0"` → `SpatieMediaLibraryFileUpload::make('attachments')->collection('attachments')->multiple()`.
- **Spatie Permission:** manage roles/permissions with a vetted plugin (e.g. Filament Shield — check v5 support) or simple custom resources over `Role`/`Permission`.
- **Activity log:** a resource or relation manager over `spatie/laravel-activitylog` `Activity` model.
- **Impersonation:** community plugin (check v5), logged and restricted to super-admins.
- **Horizon/Pulse/Telescope:** navigation links gated by the same super-admin check.

## Tenancy in panels

For tenant-facing panels: `->tenant(Workspace::class, slugAttribute: 'slug')`, `User implements HasTenants` (`getTenants()`, `canAccessTenant()`), tenant registration/profile pages. Filament scopes resources to the tenant automatically through relationships — still keep policies.

## Testing

```php
livewire(ListInvoices::class)
    ->assertCanSeeTableRecords($invoices)
    ->searchTable($invoices->first()->number)
    ->assertCanSeeTableRecords($invoices->take(1));

livewire(CreateInvoice::class)
    ->fillForm(['number' => '', 'customer_id' => $customer->id])
    ->call('create')
    ->assertHasFormErrors(['number' => 'required']);

$this->actingAs(User::factory()->create(['is_super_admin' => false]))
    ->get('/admin')->assertForbidden();
```
