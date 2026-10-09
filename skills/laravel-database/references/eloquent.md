# Eloquent models (DB-10 … DB-14)

## Model template

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Invoice extends Model
{
    use BelongsToTenant;
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = ['customer_id', 'number', 'issued_on', 'due_on', 'notes'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issued_on' => 'immutable_date',
            'due_on' => 'immutable_date',
            'sent_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
            'total_cents' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<InvoiceLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->whereNull('paid_at')->where('due_on', '<', today());
    }

    public function isOverdue(): bool
    {
        return $this->paid_at === null && $this->due_on->isPast();
    }
}
```

Notes:
- `final` on models is fine unless a package needs to extend them; drop it if Filament/plugins complain.
- Laravel 13 supports PHP attributes for model configuration (table, fillable, hidden…). Choose attributes **or** properties per project and record it in `CLAUDE.md`. Check the exact attribute names in the 13.x docs.
- ULID route key with a separate `ulid` column: generate it in a `creating` hook or use `HasUlids` with `uniqueIds()` returning `['ulid']`.
- `$hidden` for anything that must never be serialized (tokens, internal notes) — but prefer explicit Resources/DTOs for output.

## Strictness (DB-10)

```php
Model::shouldBeStrict(! app()->isProduction());
Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
    if (app()->isProduction()) {
        Log::warning('Lazy loading violation', ['model' => $model::class, 'relation' => $relation]);
        return;
    }
    throw new LazyLoadingViolationException($model, $relation);
});
```

## Casts worth using

| Cast | Use |
|---|---|
| Enum class | statuses, types |
| `immutable_datetime` / `immutable_date` | all dates |
| `encrypted`, `encrypted:array` | sensitive data (SEC-90) |
| `AsCollection::of(Dto::class)`, `AsArrayObject` | structured JSON |
| `hashed` | passwords |
| custom `CastsAttributes` | Money, Address value objects |

## Relationships

- `belongsToMany` with `->withTimestamps()` and a pivot model (`->using(Membership::class)`) when the pivot has data (role, joined_at).
- `hasOneOfMany` (`latestOfMany`) for "latest payment" instead of loading all.
- `chaperone()` on `hasMany` to hydrate the inverse without extra queries.
- Polymorphic relations: register a `Relation::enforceMorphMap([...])` so class names aren't stored in the DB.

## Observers & events (DB-14)

```php
#[ObservedBy(InvoiceObserver::class)]
```

Observers: generate ULIDs/numbers, clear caches, touch parents. Not: sending emails, charging cards, calling APIs — those belong in Actions + queued listeners.

## Soft deletes & pruning

- Soft deletes where users expect restore or legal retention. Remember unique indexes must account for `deleted_at`.
- `Prunable` / `MassPrunable` + `Schedule::command('model:prune')->daily()` for expired data.
