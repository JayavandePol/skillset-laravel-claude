# PHP style

Pint enforces formatting; this file covers what a formatter can't.

## File header

```php
<?php

declare(strict_types=1);

namespace App\Actions\Boards;
```

## Types

- Typed properties, parameters and returns everywhere, including `void` and `never`.
- Nullable only when null is a meaningful value; prefer defaults or separate methods.
- Union types sparingly; never `mixed` in app code unless wrapping a truly dynamic API.
- PHPDoc only for what PHP can't express: generics (`Collection<int, User>`), array shapes (`array{id: int, name: string}`), `@throws` for domain exceptions.
- Larastan understands Eloquent; add `@property-read` docblocks only if not using `laravel-ide-helper` or model attributes.

## Classes

- `final` by default; `final readonly` for value objects, DTOs, Actions without mutable state.
- Constructor promotion for dependencies.
- No static state in services (breaks Octane and tests).
- Max ~200 lines per class as a smell threshold — split by responsibility.

## Control flow

```php
// Prefer
public function discountFor(Customer $customer): int
{
    if (! $customer->isActive()) {
        return 0;
    }

    return match ($customer->tier) {
        Tier::Gold => 15,
        Tier::Silver => 10,
        default => 0,
    };
}
```

- `match` over `switch`; early returns over nested `if`.
- Strict comparisons (`===`, `in_array(..., true)`).
- Null-safe operator and null coalescing over `isset()` chains.
- First-class callables (`array_map($this->format(...), $rows)`) and arrow functions for short closures.

## Collections & queries

- Collections for in-memory transforms; push filtering to SQL when the data comes from the DB (`->where()` before `->get()`).
- Never `Model::all()` on unbounded tables; paginate, `chunkById` or `lazyById`.
- `->sole()` / `->firstOrFail()` instead of `first()` + null check when absence is an error.

## Strings & dates

- `__()` for every user-facing string (even single-language apps — it's cheap and enables NL/EN later).
- `now()`/`today()` return immutables when `Date::use(CarbonImmutable::class)` is set; store UTC, display in the user's timezone.
- Money as integer minor units with a Money value object (`brick/money` or `moneyphp/money`); never floats.

## Comments

- Explain *why*, not *what*. Delete comments that restate code.
- `// TODO(name): …` only with an issue link; don't leave anonymous TODOs.
