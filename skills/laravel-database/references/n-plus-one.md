# N+1 prevention & query performance (DB-20 … DB-23)

## 1. Make violations loud

`Model::shouldBeStrict()` (see eloquent.md) throws locally on lazy loading. Add Laravel Debugbar or Telescope locally to see query counts per request.

## 2. Load what the page renders

```php
$invoices = Invoice::query()
    ->select(['id', 'ulid', 'customer_id', 'number', 'status', 'total_cents', 'due_on'])
    ->with(['customer:id,name'])
    ->withCount('lines')
    ->withSum('payments as paid_cents', 'amount_cents')
    ->withExists(['reminders as has_reminders'])
    ->latest('issued_on')
    ->paginate(25)
    ->withQueryString();
```

- Constrained eager loads: `with(['comments' => fn ($q) => $q->latest()->limit(5)])` — note `limit` inside eager loads works per-parent in Laravel 11+.
- Nested: `with('lists.cards.assignees:id,name')`.
- After the fact: `$collection->loadMissing('customer')`.
- In resources/DTOs use `whenLoaded()` / `whenCounted()` so missing relations don't trigger queries.

## 3. Livewire & Inertia gotchas

- Livewire: computed properties (`#[Computed]`) cache per request; eager load inside them. Public model properties re-hydrate on every request — store IDs and load with relations as needed.
- Inertia: closures in props (`fn () => …`) only run when included; partial reloads avoid re-running heavy queries.
- Filament: tables call `->modifyQueryUsing(fn ($q) => $q->with(...))`; use `->counts('lines')` on columns.

## 4. Test it

```php
it('lists invoices in a constant number of queries', function () {
    $user = User::factory()->withTenant()->create();
    Invoice::factory()->count(30)->for($user->currentTenant)->create();

    DB::enableQueryLog();
    $this->actingAs($user)->get(route('invoices.index'))->assertOk();

    expect(count(DB::getQueryLog()))->toBeLessThan(12);
});
```

## 5. Big data patterns

| Need | Use |
|---|---|
| Process all rows | `Invoice::query()->lazyById(500)` / `chunkById(500, fn ...)` |
| Infinite scroll | `cursorPaginate()` |
| Bulk insert/update | `upsert($rows, ['tenant_id', 'number'], ['total_cents'])` |
| Counting large tables | cached counts or `simplePaginate` |
| Reports | dedicated query object + DB-side aggregation, cached with `Cache::flexible()` |
| Heavy exports | queued job → file → signed download link |

## 6. Caching

- Cache computed aggregates, not models with relations.
- Key by tenant: `"tenant:{$id}:dashboard:stats"`; bust in the Action that changes the data or via tags (Redis).
- `Cache::flexible($key, [300, 900], fn () => …)` for stale-while-revalidate.
