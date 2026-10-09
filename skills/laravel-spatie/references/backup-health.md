# laravel-backup, laravel-health, laravel-schedule-monitor

## spatie/laravel-backup (SPA-06)

```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

Config essentials (`config/backup.php`):
- `source.files.include` → `storage/app` (exclude caches); `source.databases` → `['pgsql']`.
- `destination.disks` → an **off-site** S3 bucket (different provider/region than the app).
- `backup.password` + `encryption` → `'default'` (AES-256) for encrypted zips.
- `notifications` → mail/Slack on failure.
- `monitor_backups` → max age 1 day, max storage size.
- `cleanup.default_strategy` → keep daily 7, weekly 4, monthly 6 (match your retention policy / GDPR).

Database dumps need client binaries in the image (`postgresql-client` / `mariadb-client`, matching server major).

```php
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30')->onOneServer();
Schedule::command('backup:monitor')->daily()->at('03:00');
```

Test restores quarterly: download zip → restore DB into staging → boot app.

## spatie/laravel-health (SPA-07)

```bash
composer require spatie/laravel-health
php artisan vendor:publish --tag="health-config"
```

```php
// AppServiceProvider::boot
Health::checks([
    DatabaseCheck::new(),
    CacheCheck::new(),
    RedisCheck::new(),
    HorizonCheck::new(),
    ScheduleCheck::new()->heartbeatMaxAgeInMinutes(2),
    UsedDiskSpaceCheck::new()->warnWhenUsedSpaceIsAbovePercentage(70)->failWhenUsedSpaceIsAbovePercentage(90),
    DebugModeCheck::new(),
    EnvironmentCheck::new(),
    OptimizedAppCheck::new(),
    BackupsCheck::new()->locatedAt(storage_path('app/backups/*.zip')), // or check remote via backup:monitor
]);

Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute();
Schedule::command(RunHealthChecksCommand::class)->everyMinute();
```

Expose JSON for uptime monitors via `HealthCheckJsonResultsController` behind a secret/allowlisted route; keep Laravel's `/up` for container liveness.

## spatie/laravel-schedule-monitor

Tracks whether scheduled tasks ran and finished; optional Oh Dear sync. `php artisan schedule-monitor:sync` on deploy.
