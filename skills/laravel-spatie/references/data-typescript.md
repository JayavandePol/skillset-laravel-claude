# laravel-data + typescript-transformer

One class = DTO + validation + resource + TypeScript type.

```bash
composer require spatie/laravel-data spatie/laravel-typescript-transformer
php artisan vendor:publish --provider="Spatie\LaravelData\LaravelDataServiceProvider" --tag="data-config"
```

## Data object

```php
#[TypeScript]
final class InvoiceData extends Data
{
    public function __construct(
        public string $id,
        public string $number,
        public InvoiceStatus $status,
        public int $total_cents,
        public CarbonImmutable $issued_on,
        public CustomerData|Lazy $customer,
        /** @var DataCollection<int, InvoiceLineData> */
        public DataCollection|Lazy $lines,
    ) {}

    public static function fromModel(Invoice $invoice): self
    {
        return new self(
            id: $invoice->ulid,
            number: $invoice->number,
            status: $invoice->status,
            total_cents: $invoice->total_cents,
            issued_on: $invoice->issued_on,
            customer: Lazy::whenLoaded('customer', $invoice, fn () => CustomerData::from($invoice->customer)),
            lines: Lazy::whenLoaded('lines', $invoice, fn () => InvoiceLineData::collect($invoice->lines, DataCollection::class)),
        );
    }
}
```

- Input DTOs (`StoreInvoiceData`) can validate with attributes (`#[Max(500)]`) — but keep FormRequests for authorization and complex rules, or use Data's `authorize()`; choose one style per project.
- Use `Lazy` / `Optional` to avoid N+1 and to mirror Inertia partial props.
- Constructor property naming: pick snake_case or camelCase for output and configure name mapping (`#[MapOutputName(SnakeCaseMapper::class)]`) consistently.

## TypeScript generation

```bash
php artisan typescript:transform     # writes resources/js/types/generated.d.ts (configure path)
```

- Run in `composer run dev` (watch) or as a pre-build step; CI checks the generated file is up to date (`git diff --exit-code`).
- Enums with `#[TypeScript]` become TS union types.
- Check the major version's config format — v3 of typescript-transformer changed configuration.
