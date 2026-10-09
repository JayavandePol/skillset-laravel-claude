# Search with Scout

```bash
composer require laravel/scout
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

| Driver | When |
|---|---|
| `database` | start here: uses LIKE/full-text on MySQL/PG, no extra service |
| `meilisearch` | typo tolerance, facets, instant search — easy self-host (Docker) |
| `typesense` | similar, good performance, self-hostable |
| `algolia` | hosted, paid |
| pgvector / AI SDK | semantic search (Laravel 13) — see ai.md |

```php
final class Customer extends Model
{
    use Searchable;

    /** @return array<string, mixed> */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'email' => $this->email,
            'city' => $this->city,
        ];
    }

    public function shouldBeSearchable(): bool { return ! $this->trashed(); }
}

Customer::search($term)->where('tenant_id', $tenantId)->paginate(20);  // ECO-05: always tenant filter
```

- Meilisearch: configure `filterableAttributes` (`tenant_id`) and `sortableAttributes` in `config/scout.php` → `meilisearch.index-settings`, then `php artisan scout:sync-index-settings`.
- `SCOUT_QUEUE=true` so indexing is queued.
- Import: `php artisan scout:import "App\Models\Customer"`; flush on schema changes.
- Global search (⌘K): query several models in parallel, return a small typed result list, respect policies.
