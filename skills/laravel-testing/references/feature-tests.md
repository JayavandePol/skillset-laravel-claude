# Feature test patterns

## HTTP + authorization matrix (TST-02)

```php
describe('PUT /invoices/{invoice}', function () {
    it('updates for members with permission', function () {
        $user = actingAsTenantUser(Role::Member);
        $invoice = Invoice::factory()->for($user->currentTenant)->create();

        $this->put(route('invoices.update', $invoice), validInvoice())
            ->assertRedirect(route('invoices.show', $invoice))
            ->assertSessionHasNoErrors();
    });

    it('forbids viewers', function () {
        $user = actingAsTenantUser(Role::Viewer);
        $invoice = Invoice::factory()->for($user->currentTenant)->create();

        $this->put(route('invoices.update', $invoice), validInvoice())->assertForbidden();
    });

    it('hides invoices of other tenants', function () {
        actingAsTenantUser();
        $foreign = Invoice::factory()->create();

        $this->put(route('invoices.update', $foreign), validInvoice())->assertNotFound();
    });

    it('requires authentication', function () {
        $this->put(route('invoices.update', Invoice::factory()->create()), validInvoice())->assertRedirect(route('login'));
    });
});
```

## Inertia (TST-03)

```php
use Inertia\Testing\AssertableInertia as Assert;

it('renders the invoice page', function () {
    $user = actingAsTenantUser();
    $invoice = Invoice::factory()->for($user->currentTenant)->hasLines(2)->create();

    $this->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/show')
            ->where('invoice.id', $invoice->ulid)
            ->has('invoice.lines', 2)
            ->missing('invoice.tenant_id')
            ->where('can.update', true));
});

it('loads deferred payments', function () {
    // partial reload
    $this->get(route('invoices.show', $invoice), [
        'X-Inertia' => 'true',
        'X-Inertia-Partial-Component' => 'invoices/show',
        'X-Inertia-Partial-Data' => 'payments',
    ])->assertJsonPath('props.payments', fn ($p) => is_array($p));
});
```

Recent inertia-laravel versions also offer `->loadDeferredProps()` / `->reloadOnly()` helpers on the assertion — check docs.

## Livewire

```php
Livewire::actingAs($user)
    ->test(EditInvoice::class, ['invoice' => $invoice])
    ->set('form.notes', str_repeat('x', 6000))
    ->call('save')
    ->assertHasErrors(['form.notes' => 'max']);
```

## Filament

```php
livewire(EditInvoice::class, ['record' => $invoice->getRouteKey()])
    ->fillForm(['notes' => 'Updated'])
    ->call('save')
    ->assertHasNoFormErrors();
```

## API

```php
Sanctum::actingAs($user, ['invoices:write']);
$this->postJson('/api/v1/invoices', $payload)
    ->assertCreated()
    ->assertHeader('Location')
    ->assertJsonPath('data.status', 'draft');

$this->postJson('/api/v1/invoices', [])->assertUnprocessable()->assertJsonValidationErrors(['lines']);
```

## Query count (DB-22)

```php
DB::enableQueryLog();
$this->get(route('invoices.index'))->assertOk();
expect(count(DB::getQueryLog()))->toBeLessThan(12);
```
