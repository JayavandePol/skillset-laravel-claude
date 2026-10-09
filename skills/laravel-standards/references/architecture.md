# Architecture: where code goes

## Directory layout

```
app/
├── Actions/<Domain>/        # write operations: CreateInvoice, ArchiveBoard (STD-01)
├── Data/                    # DTOs / laravel-data objects (STD-05)
├── Enums/                   # backed enums (STD-06)
├── Events/  Listeners/      # domain events + reactions (STD-07)
├── Exceptions/              # domain exceptions (STD-40)
├── Http/
│   ├── Controllers/         # thin, often invokable (STD-02)
│   ├── Middleware/
│   ├── Requests/            # one FormRequest per write endpoint
│   └── Resources/           # API/Inertia resources
├── Jobs/
├── Livewire/  or resources/views/livewire   # if Livewire
├── Filament/                # if Filament
├── Mail/  Notifications/
├── Models/
│   ├── Concerns/            # model traits (BelongsToTenant, HasUlid)
│   └── Builders/            # custom Eloquent builders for big models (optional)
├── Policies/
├── Providers/
├── Queries/                 # read-side query objects (STD-04)
├── Rules/                   # custom validation rules
├── Services/<System>/       # external integrations (STD-03)
└── Support/                 # small framework-level helpers, value objects
```

For large apps (> ~40 models), group by domain module instead — `app/Domain/Billing/{Actions,Models,Data,Events}` — but keep Laravel's conventional folders (`Http`, `Providers`, `Console`) at the top level. Decide once, record it in `CLAUDE.md`.

## Actions

```php
<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Data\InvoiceData;
use App\Events\InvoiceCreated;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Numbering\InvoiceNumberGenerator;
use Illuminate\Support\Facades\DB;

final readonly class CreateInvoice
{
    public function __construct(private InvoiceNumberGenerator $numbers) {}

    public function handle(User $actor, Customer $customer, InvoiceData $data): Invoice
    {
        return DB::transaction(function () use ($actor, $customer, $data): Invoice {
            $invoice = $customer->invoices()->create([
                'number' => $this->numbers->next($customer->tenant_id),
                'issued_at' => $data->issuedAt,
                'created_by' => $actor->id,
            ]);

            $invoice->lines()->createMany($data->lines->toArray());

            InvoiceCreated::dispatch($invoice);

            return $invoice->refresh();
        });
    }
}
```

Rules of thumb:
- Name = verb + noun, describing the business operation, not CRUD (`PublishPost`, `ApproveExpense`, not `PostService::update`).
- Inputs are models + DTOs, never the `Request`. That makes Actions callable from controllers, Livewire, Filament, jobs, commands, seeders and tests alike.
- Actions may call other Actions. Avoid deep chains (>3); extract a coordinating Action if needed.
- Authorization is done **before** the Action (FormRequest/policy), unless the Action is a security boundary itself (e.g. called from a public API and a job) — then authorize inside too.
- Return the created/updated model or a result DTO; don't return HTTP responses.

## Controllers

```php
final class InvoiceController
{
    public function store(StoreInvoiceRequest $request, Customer $customer, CreateInvoice $createInvoice): RedirectResponse
    {
        $invoice = $createInvoice->handle($request->user(), $customer, $request->toData());

        return to_route('invoices.show', $invoice)->with('success', __('Invoice created.'));
    }
}
```

- Resource controllers (`index/show/create/store/edit/update/destroy`) for CRUD; invokable controllers for anything else (`SendInvoiceController`). Don't add custom methods to resource controllers.
- `FormRequest::toData()` (or `InvoiceData::from($request)`) builds the DTO.

## Services

```php
interface PaymentGateway
{
    public function createPayment(Money $amount, string $description, string $redirectUrl): PaymentIntent;
}

final readonly class MolliePaymentGateway implements PaymentGateway { /* wraps the SDK */ }

// AppServiceProvider::register
$this->app->bind(PaymentGateway::class, MolliePaymentGateway::class);
```

Tests swap the binding with a fake. SDK types never leak out of the service — return your own DTOs.

## Query objects

```php
final readonly class OverdueInvoicesQuery
{
    public function __construct(private int $tenantId) {}

    /** @return Builder<Invoice> */
    public function builder(): Builder
    {
        return Invoice::query()
            ->where('tenant_id', $this->tenantId)
            ->whereNull('paid_at')
            ->where('due_at', '<', now())
            ->with(['customer:id,name'])
            ->orderBy('due_at');
    }
}
```

Return a Builder so callers can paginate, count or export.

## DTOs

Plain PHP:

```php
final readonly class InvoiceData
{
    /** @param Collection<int, InvoiceLineData> $lines */
    public function __construct(
        public CarbonImmutable $issuedAt,
        public Collection $lines,
        public ?string $notes = null,
    ) {}
}
```

Or `spatie/laravel-data` when you also want validation, TS type generation and Inertia/Livewire integration — pick one approach per project.

## Enums

```php
enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("invoices.status.{$this->value}");
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, match ($this) {
            self::Draft => [self::Sent, self::Cancelled],
            self::Sent => [self::Paid, self::Cancelled],
            self::Paid, self::Cancelled => [],
        }, true);
    }
}
```

For complex state machines use `spatie/laravel-model-states`.

## Events, listeners, jobs

- Event = something that happened (past tense: `InvoicePaid`). Listener = reaction. Mark listeners `ShouldQueue` unless they must run synchronously.
- Set `public bool $afterCommit = true;` on listeners/jobs/notifications dispatched inside transactions, or `'after_commit' => true` in `config/queue.php`.
- Model observers only for model-lifecycle concerns (setting ULIDs, clearing cache) — not business workflows.
- Jobs take IDs or models (serialized with `SerializesModels`), are idempotent, set `$tries`, `$backoff`, `$timeout`, and handle `failed()`.

## Service providers

- `AppServiceProvider::register()` — bindings only. `boot()` — model strictness, rate limiters, gates, macros, `Password::defaults()`, `Vite` config.
- One provider per large concern if `AppServiceProvider` grows past ~100 lines (`AuthServiceProvider`-style split is fine).

## Model strictness (always on)

```php
// AppServiceProvider::boot()
Model::shouldBeStrict(! $this->app->isProduction());
Model::automaticallyEagerLoadRelationships(); // optional, Laravel 12.8+ — explicit eager loading still preferred
DB::prohibitDestructiveCommands($this->app->isProduction());
Date::use(CarbonImmutable::class);
URL::forceHttps($this->app->isProduction());
Vite::useAggressivePrefetching();
```
