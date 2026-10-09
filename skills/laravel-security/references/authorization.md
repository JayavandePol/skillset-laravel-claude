# Authorization (SEC-10 … SEC-14)

## Layers

1. **Authentication middleware** — `auth`, `verified`, `auth:sanctum`, `password.confirm` for sensitive settings.
2. **Tenant membership middleware** — resolves the current tenant from the route, aborts for non-members, sets context (Spatie team id, global scopes).
3. **Policies** — per model ability, combining membership + permission + state (e.g. can't edit a paid invoice).
4. **Gates** — abilities without a model (`viewAdmin`, `exportReports`).
5. **Database scoping** — global scopes + scoped bindings as a safety net, not the primary check.

## Policy template

```php
final class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::InvoicesView);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->belongsToTenant($invoice->tenant_id)
            && $user->hasPermissionTo(Permission::InvoicesView);
    }

    public function update(User $user, Invoice $invoice): Response
    {
        if (! $user->belongsToTenant($invoice->tenant_id)) {
            return Response::denyAsNotFound(); // don't reveal existence across tenants
        }

        if ($invoice->status === InvoiceStatus::Paid) {
            return Response::deny(__('Paid invoices cannot be changed.'));
        }

        return $user->hasPermissionTo(Permission::InvoicesUpdate)
            ? Response::allow()
            : Response::deny();
    }
}
```

- Return `Response::denyAsNotFound()` for cross-tenant access so IDs can't be probed.
- Check **permissions**, not role names, in policies — roles are bundles of permissions that admins may reconfigure.
- Laravel auto-discovers `App\Policies\<Model>Policy`; register manually for non-standard names.
- `Gate::before()` only for a true super-admin, and never for tenant users.

## Applying policies

```php
Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->can('update', 'invoice');
// or in FormRequest::authorize(), or Gate::authorize('update', $invoice) in controller
```

Resource controllers can use `$this->authorizeResource()`-style mapping only if the base controller supports it in the installed Laravel version; otherwise authorize per route/request.

## Scoped bindings

```php
Route::scopeBindings()->group(function () {
    Route::get('customers/{customer}/invoices/{invoice}', ...); // invoice must belong to customer
});
```

## Livewire specifics

```php
final class EditInvoice extends Component
{
    #[Locked]
    public int $invoiceId;

    public function save(UpdateInvoice $action): void
    {
        $invoice = Invoice::findOrFail($this->invoiceId);
        $this->authorize('update', $invoice);   // every action, every time
        $this->validate();
        $action->handle(auth()->user(), $invoice, InvoiceData::from($this->form));
    }
}
```

Never trust model IDs or prices held in public properties.

## Tests (required)

```php
it('forbids users from another tenant', function () {
    $invoice = Invoice::factory()->create();
    $outsider = User::factory()->withTenant()->create();

    $this->actingAs($outsider)
        ->put(route('invoices.update', $invoice), validInvoicePayload())
        ->assertNotFound();
});
```

One allowed + one denied + one cross-tenant test per policy method that guards a route.
