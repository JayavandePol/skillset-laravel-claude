# Phase 2 — Database, Migrations & Eloquent

## Migration rules

- One migration per table (plus separate ones for later alterations). Order files so parents come before children.
- Use `foreignId()->constrained()` with an explicit delete strategy on **every** FK — decide per relation:
  - `cascadeOnDelete()` for owned children (board → lists → cards).
  - `nullOnDelete()` for optional references (card → assigned user who left).
  - `restrictOnDelete()` for financial/legal records (invoice → customer).
- Index what you filter/sort by, in the order you query: `(workspace_id, archived_at, position)`, not three separate indexes.
- Use `->comment()` on non-obvious columns; it shows in DB tools and helps future agents.
- Never edit a migration that has run in production — add a new one.
- Large-table alterations: add nullable column → backfill in a queued job → add constraint in a later migration.

### Example

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->decimal('position', 20, 10)->comment('fractional index for drag & drop ordering');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['board_list_id', 'archived_at', 'position']);
            $table->index(['workspace_id', 'due_at']);
        });

        Schema::create('card_user', function (Blueprint $table) {
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['card_id', 'user_id']);
            $table->index('user_id'); // reverse lookups: "cards assigned to me"
        });
    }
};
```

### PostgreSQL vs. MariaDB notes

| Topic | PostgreSQL | MariaDB |
|---|---|---|
| JSON | `jsonb` + GIN index | `json` (alias of LONGTEXT + check); index via generated columns |
| Partial indexes | yes — e.g. unique `slug` where `deleted_at is null` | no — use generated column trick or include `deleted_at` in unique |
| Case-insensitive search | `ILIKE`, `citext`, or `pg_trgm` index | default collation is case-insensitive |
| Full-text | `tsvector` + GIN; Scout database driver supports it | `FULLTEXT` index |
| Vectors / AI search | `pgvector` (Laravel 13 has native vector query support) | MariaDB 11.7+ has VECTOR type — check Laravel support |
| Transactional DDL | yes (failed migration rolls back) | no |

Write migrations that work on the chosen DB only — don't spend effort on portability unless the user needs both. For PG partial indexes use `DB::statement()` in the migration.

## Models

```php
#[ObservedBy(CardObserver::class)]
final class Card extends Model
{
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    protected $fillable = ['title', 'description', 'due_at', 'position', 'board_list_id'];

    protected function casts(): array
    {
        return [
            'due_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
            'position' => 'decimal:10',
        ];
    }

    public function getRouteKeyName(): string { return 'ulid'; }

    public function list(): BelongsTo { return $this->belongsTo(BoardList::class, 'board_list_id'); }
    public function assignees(): BelongsToMany { return $this->belongsToMany(User::class)->withTimestamps(); }
    public function comments(): HasMany { return $this->hasMany(Comment::class)->latest(); }

    #[Scope]
    protected function active(Builder $query): void { $query->whereNull('archived_at'); }

    #[Scope]
    protected function dueBefore(Builder $query, CarbonInterface $date): void { $query->where('due_at', '<', $date); }
}
```

Laravel 13 also lets you configure models with PHP attributes instead of properties (`$table`, `$hidden`, …). Check the release notes for exact attribute names before using them; mixing styles in one project is confusing — pick one.

### Tenancy scope

```php
trait BelongsToWorkspace
{
    protected static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope('workspace', function (Builder $query) {
            if ($workspace = app(CurrentWorkspace::class)->get()) {
                $query->where($query->qualifyColumn('workspace_id'), $workspace->id);
            }
        });

        static::creating(function (Model $model) {
            $model->workspace_id ??= app(CurrentWorkspace::class)->get()?->id;
        });
    }

    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
}
```

The global scope is a safety net — Policies and `scopeBindings()` remain the primary protection. Filament (super-admin) bypasses with `withoutGlobalScope('workspace')`.

## N+1 prevention strategy

1. **Fail loudly in dev** (AppServiceProvider::boot):

   ```php
   Model::shouldBeStrict(! app()->isProduction()); // lazy loading, silently discarded attributes, missing attributes
   DB::prohibitDestructiveCommands(app()->isProduction());
   ```

   In production, log lazy loading instead of throwing:

   ```php
   Model::handleLazyLoadingViolationUsing(fn ($model, $relation) =>
       Log::warning("Lazy loading {$relation} on ".$model::class));
   ```

2. **Eager load in the controller / query object**, with column selection and constrained relations:

   ```php
   $board->load([
       'lists' => fn ($q) => $q->active()->orderBy('position'),
       'lists.cards' => fn ($q) => $q->active()->orderBy('position')
           ->withCount(['comments', 'attachments'])
           ->with(['assignees:id,name,avatar_path', 'labels:id,name,color']),
   ]);
   ```

3. Use `withCount`, `withExists`, `withSum` instead of loading collections to count them.
4. Use `chunkById()` / `lazyById()` for batch jobs; `cursorPaginate()` for infinite scroll.
5. Laravel 12.8+ has `Model::automaticallyEagerLoadRelationships()` — useful, but explicit eager loading stays the default because it documents what the page needs.
6. Shape responses with API Resources (or laravel-data) so the React page only receives the fields it uses — this also prevents leaking columns.
7. Verify: Laravel Debugbar or Telescope locally, query count assertions in tests for hot pages:

   ```php
   DB::enableQueryLog();
   $this->get(route('boards.show', [$workspace, $board]))->assertOk();
   expect(count(DB::getQueryLog()))->toBeLessThan(15);
   ```

## Factories & seeders

- Factory per model with meaningful states (`->archived()`, `->overdue()`).
- `DatabaseSeeder` creates a demo workspace with a known login (store demo credentials in `.env.example`/`README`, never real ones) so browser verification has data immediately.
