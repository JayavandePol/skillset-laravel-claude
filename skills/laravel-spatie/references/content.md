# Content packages: translatable, sluggable, tags, settings

## laravel-translatable

JSON columns with per-locale values — good for NL/EN product names, CMS content.

```php
final class Product extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];
}

$product->setTranslation('name', 'nl', 'Fiets')->setTranslation('name', 'en', 'Bicycle')->save();
$product->name; // current app locale, with fallback (config translatable.fallback_locale)
Product::whereLocale('name', 'nl')->get();
```

- Column type `json` (PG `jsonb`). Validate as `array` with per-locale rules: `'name.nl' => ['required', 'string', 'max:255']`.
- Searching translated JSON is slower — index with generated columns (MySQL/MariaDB) or GIN (PG), or use Scout.
- Filament: the Spatie translatable plugin (check v5 support).

## laravel-sluggable

```php
use HasSlug;

public function getSlugOptions(): SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('name')
        ->saveSlugsTo('slug')
        ->doNotGenerateSlugsOnUpdate()     // stable URLs
        ->extraScope(fn ($q) => $q->where('tenant_id', $this->tenant_id));
}
```

Unique index on `(tenant_id, slug)`. For translated slugs use `HasTranslatableSlug`. Old slugs → keep a redirect table if URLs are public/SEO-relevant.

## laravel-tags

Polymorphic tags with types and translations: `$post->attachTags(['laravel', 'php'], 'topic')`; `Post::withAnyTags(['php'])`. Scope tags per tenant with a custom Tag model or `type` prefix.

## laravel-settings

Typed settings classes stored in the DB (company name, invoice prefix, VAT defaults) — better than a key/value table.

```php
final class InvoiceSettings extends Settings
{
    public string $prefix;
    public int $payment_term_days;

    public static function group(): string { return 'invoices'; }
}
```

Created via settings migrations (`php artisan make:settings-migration`). For per-tenant settings, use a tenant-keyed repository or a JSON `settings` column on the tenant with a DTO cast instead.
