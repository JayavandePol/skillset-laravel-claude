# Queues, Horizon, scheduling

## Queue setup

```dotenv
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
```

```php
// config/queue.php → redis connection
'after_commit' => true,
'retry_after' => 120,      // > longest job timeout
```

## Horizon

```bash
composer require laravel/horizon
php artisan horizon:install
```

```php
// config/horizon.php (excerpt)
'environments' => [
    'production' => [
        'high' => ['connection' => 'redis', 'queue' => ['high'], 'balance' => 'auto', 'minProcesses' => 1, 'maxProcesses' => 5, 'tries' => 3, 'timeout' => 60],
        'default' => ['connection' => 'redis', 'queue' => ['default', 'media'], 'balance' => 'auto', 'maxProcesses' => 10, 'tries' => 3, 'timeout' => 300],
        'low' => ['connection' => 'redis', 'queue' => ['low'], 'maxProcesses' => 2, 'timeout' => 900],
    ],
],
```

Gate the dashboard: `Gate::define('viewHorizon', fn (User $u) => $u->is_super_admin);`. Deploy: `php artisan horizon:terminate` (supervisor/container restarts it). Snapshot metrics: `Schedule::command('horizon:snapshot')->everyFiveMinutes();`.

## Job template

```php
final class GenerateInvoicePdf implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 120;
    public int $uniqueFor = 600;

    public function __construct(public readonly int $invoiceId) {}

    /** @return list<int> */
    public function backoff(): array { return [10, 60, 300, 900]; }

    public function uniqueId(): string { return (string) $this->invoiceId; }

    /** @return list<object> */
    public function middleware(): array
    {
        return [new WithoutOverlapping($this->invoiceId), (new ThrottlesExceptions(5, 5 * 60))->backoff(5)];
    }

    public function handle(RenderInvoicePdf $render): void
    {
        $invoice = Invoice::withoutGlobalScopes()->findOrFail($this->invoiceId);
        // set tenant context before tenant-scoped work
        $render->handle($invoice);
    }

    public function failed(Throwable $e): void
    {
        report($e);
    }
}
```

- Dispatch: `GenerateInvoicePdf::dispatch($invoice->id)->onQueue('low');`
- Batches with progress: `Bus::batch($jobs)->name('Import customers')->allowFailures()->dispatch();` — show progress via polling/broadcast.
- Chains for ordered steps: `Bus::chain([...])->dispatch();`
- Failed jobs: `failed_jobs` table (UUIDs), `queue:retry`, alerts via Horizon notifications/Sentry; `Schedule::command('queue:prune-failed --hours=168')->daily();`

## Scheduling (routes/console.php)

```php
Schedule::command('model:prune')->daily()->onOneServer();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('backup:run')->dailyAt('01:30')->onOneServer();
Schedule::job(new SendOverdueReminders)->weekdays()->at('08:00')->timezone('Europe/Amsterdam')->onOneServer()->withoutOverlapping();
```

Run: `php artisan schedule:work` (container) or cron `* * * * * php artisan schedule:run`. Monitor with `spatie/laravel-schedule-monitor` or health heartbeat.

## Testing

```php
Queue::fake();
// act
Queue::assertPushed(GenerateInvoicePdf::class, fn ($job) => $job->invoiceId === $invoice->id);

Bus::fake(); Bus::assertBatched(fn (PendingBatch $b) => $b->jobs->count() === 10);
```

Also test `handle()` directly for logic.
