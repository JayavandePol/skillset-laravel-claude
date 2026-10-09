# Pest setup

## Structure

```
tests/
├── Pest.php              # uses(), global helpers, expectations
├── ArchTest.php          # architecture rules (from laravel-standards)
├── Unit/                 # pure logic: value objects, enums, calculators
├── Feature/
│   ├── Actions/          # Action behaviour with DB
│   ├── Http/             # routes, controllers, Inertia pages
│   ├── Api/V1/           # API endpoints
│   ├── Livewire/         # Livewire components
│   ├── Filament/         # admin resources
│   └── Policies/
└── Browser/              # Pest browser (Playwright) tests
```

## tests/Pest.php

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(function (): void {
        Http::preventStrayRequests();
        $this->freezeTime();
    })
    ->in('Feature', 'Browser');

function actingAsTenantUser(Role $role = Role::Owner): User
{
    $user = User::factory()->withTenant(role: $role)->create();
    test()->actingAs($user);
    app(CurrentTenant::class)->set($user->currentTenant);
    setPermissionsTeamId($user->currentTenant->id);

    return $user;
}
```

## Style

```php
describe('CreateInvoice', function () {
    it('numbers invoices sequentially per tenant', function () {
        $user = actingAsTenantUser();
        $customer = Customer::factory()->for($user->currentTenant)->create();

        $first = app(CreateInvoice::class)->handle($user, $customer, InvoiceData::fake());
        $second = app(CreateInvoice::class)->handle($user, $customer, InvoiceData::fake());

        expect($second->number)->toBe(next_number($first->number));
    });

    it('rejects invalid lines', function (array $line, string $error) {
        actingAsTenantUser();

        $this->post(route('invoices.store', Customer::factory()->create()), ['lines' => [$line]])
            ->assertSessionHasErrors($error);
    })->with([
        'negative quantity' => [['description' => 'x', 'quantity' => -1, 'unit_price' => 100], 'lines.0.quantity'],
        'missing description' => [['quantity' => 1, 'unit_price' => 100], 'lines.0.description'],
    ]);
});
```

- `describe` per class/feature, `it` reads as behaviour.
- Datasets for validation matrices.
- `expect()` chains; custom expectations in Pest.php for domain checks (`toBeMoney`).

## Fakes cheat sheet

| Fake | Assertion |
|---|---|
| `Queue::fake()` | `assertPushed`, `assertPushedOn`, `assertNothingPushed` |
| `Bus::fake()` | `assertDispatched`, `assertBatched`, `assertChained` |
| `Event::fake([InvoicePaid::class])` | `assertDispatched` (fake only listed events to keep others) |
| `Mail::fake()` | `assertQueued(InvoiceMail::class, fn ($m) => $m->hasTo($email))` |
| `Notification::fake()` | `assertSentTo($user, InvoiceOverdue::class)` |
| `Http::fake(['api.mollie.com/*' => Http::response([...])])` | `Http::assertSent(fn ($r) => ...)` |
| `Storage::fake('s3')` | `Storage::disk('s3')->assertExists($path)` |
| `Feature::define(...)` | Pennant |
| `$this->travelTo(...)` / `freezeTime()` | time |
