# spatie/laravel-query-builder (SPA-08)

```bash
composer require spatie/laravel-query-builder
```

```php
$invoices = QueryBuilder::for(Invoice::query()->whereBelongsTo($tenant))
    ->allowedFilters([
        AllowedFilter::exact('status'),
        AllowedFilter::exact('customer', 'customer_id'),
        AllowedFilter::partial('number'),
        AllowedFilter::scope('overdue'),
        AllowedFilter::callback('issued_between', fn (Builder $q, array $v) => $q->whereBetween('issued_on', [$v[0], $v[1]])),
    ])
    ->allowedSorts(['issued_on', 'number', AllowedSort::field('total', 'total_cents')])
    ->allowedIncludes(['customer', 'lines', AllowedInclude::count('linesCount')])
    ->allowedFields(['invoices.id', 'invoices.number', 'invoices.status'])
    ->defaultSort('-issued_on')
    ->paginate(min(request()->integer('per_page', 25), 100))
    ->appends(request()->query());
```

- Unknown filters/sorts throw `InvalidFilterQuery` (400) by default — keep that behaviour.
- Base query is already tenant-scoped; includes must respect policies (don't include relations the user can't see).
- Use the same allowlists for Inertia index pages (`?filter[status]=paid&sort=-issued_on`).
- Callback filters: validate the values (dates, arrays) — they bypass FormRequests.
