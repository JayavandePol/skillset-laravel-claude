# Feature flags with Pennant

```bash
composer require laravel/pennant
php artisan vendor:publish --provider="Laravel\Pennant\PennantServiceProvider"
php artisan migrate
```

Class-based features (discoverable, testable):

```php
final class AiSummaries
{
    public function resolve(Tenant $tenant): bool
    {
        return $tenant->plan->includes(PlanFeature::Ai) || $tenant->is_beta;
    }
}
```

```php
Feature::for($tenant)->active(AiSummaries::class);
Route::middleware(EnsureFeaturesAreActive::using(AiSummaries::class));
@feature(AiSummaries::class) … @endfeature
```

- Scope to tenant for B2B (`Feature::resolveScopeUsing(fn () => app(CurrentTenant::class)->get())`).
- Share active flags to the frontend as booleans (Inertia shared data) — still check server-side (ECO-06).
- Gradual rollout: `Lottery::odds(1, 10)` in `resolve`, results are stored so users don't flip.
- Remove a flag: `Feature::purge(AiSummaries::class)` after removing code paths.
- Tests: `Feature::define(AiSummaries::class, true)` / `Feature::activate()`.
