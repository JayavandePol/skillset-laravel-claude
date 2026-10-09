# spatie/laravel-activitylog

```bash
composer require spatie/laravel-activitylog
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan migrate
```

## Model logging

```php
final class Invoice extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'due_on', 'total_cents', 'customer_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('invoices');
    }
}
```

- `logOnly` allowlist (never `logAll` on models with sensitive fields) — SPA-05.
- Add tenant id: `tapActivity()` or a custom Activity model with `tenant_id` column (index it), set from `CurrentTenant`.

## Manual business events

```php
activity('invoices')
    ->performedOn($invoice)
    ->causedBy($actor)
    ->withProperties(['channel' => 'email'])
    ->event('sent')
    ->log('Invoice sent');
```

Log from Actions (or queued listeners), not controllers.

## Retention

```php
// config/activitylog.php → 'delete_records_older_than_days' => 365
Schedule::command('activitylog:clean')->daily();
```

## Displaying

Activity feed per model: `Activity::forSubject($invoice)->with('causer:id,name')->latest()->paginate(20)` → DTO with translated descriptions. Filament: relation manager or a custom table.
