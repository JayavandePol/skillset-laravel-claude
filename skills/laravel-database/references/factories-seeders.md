# Factories & seeders (DB-40, DB-41)

## Factory template

```php
/** @extends Factory<Invoice> */
final class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $issued = CarbonImmutable::instance(fake()->dateTimeBetween('-3 months'));

        return [
            'tenant_id' => Tenant::factory(),
            'customer_id' => fn (array $attrs) => Customer::factory()->state(['tenant_id' => $attrs['tenant_id']]),
            'number' => fake()->unique()->numerify('2026-####'),
            'status' => InvoiceStatus::Draft,
            'issued_on' => $issued,
            'due_on' => $issued->addDays(30),
            'total_cents' => fake()->numberBetween(1_000, 500_000),
        ];
    }

    public function paid(): self
    {
        return $this->state(fn () => ['status' => InvoiceStatus::Paid, 'paid_at' => now()]);
    }

    public function overdue(): self
    {
        return $this->state(fn () => ['status' => InvoiceStatus::Sent, 'due_on' => today()->subDays(10)]);
    }
}
```

- Related models default to factories so `Invoice::factory()->create()` works standalone.
- Keep the tenant consistent across related factories (closure referencing `$attrs`).
- `User::factory()->withTenant()` state that creates a tenant, membership and role — used in nearly every test.

## Seeders

```
database/seeders/
├── DatabaseSeeder.php           # calls the others based on environment
├── ReferenceDataSeeder.php      # roles, permissions, plans, countries — idempotent, safe in production
└── DemoSeeder.php               # local/staging only: demo tenant, users, realistic data
```

```php
public function run(): void
{
    $this->call(ReferenceDataSeeder::class);

    if (app()->environment(['local', 'staging'])) {
        $this->call(DemoSeeder::class);
    }
}
```

- Idempotent: `firstOrCreate`/`updateOrCreate`/`upsert`.
- Demo login documented in the README (e.g. `demo@example.test` / password from `.env.example`), never real credentials.
- Production reference data can also be applied via migrations when it must exist for code to work.
