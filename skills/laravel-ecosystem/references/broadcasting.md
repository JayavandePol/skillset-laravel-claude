# Broadcasting with Reverb

```bash
php artisan install:broadcasting       # choose Reverb; installs laravel/reverb, Echo config, channels.php
```

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=…
REVERB_APP_KEY=…
REVERB_APP_SECRET=…
REVERB_HOST=ws.example.com
REVERB_PORT=443
REVERB_SCHEME=https
```

Run: `php artisan reverb:start --host=0.0.0.0 --port=8080` (own container); proxy with websocket support (NPM "Websockets Support", Traefik works by default). Scale horizontally with Redis scaling enabled (`REVERB_SCALING_ENABLED=true`). Laravel 13 adds a database driver option for Reverb — check docs.

## Events

```php
final class InvoicePaid implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("tenants.{$this->invoice->tenant->ulid}")];
    }

    public function broadcastAs(): string { return 'invoice.paid'; }

    /** @return array<string, mixed> */
    public function broadcastWith(): array { return ['id' => $this->invoice->ulid]; }
}
```

## Channel authorization (ECO-04)

```php
Broadcast::channel('tenants.{ulid}', fn (User $user, string $ulid) => $user->tenants()->where('ulid', $ulid)->exists());

Broadcast::channel('boards.{board}', function (User $user, Board $board) {   // model binding works
    return $user->can('view', $board) ? ['id' => $user->id, 'name' => $user->name] : false; // presence data
});
```

## Client

- React: `@laravel/echo-react` → `useEcho('tenants.'+ulid, '.invoice.paid', cb)`, `useEchoPresence`.
- Vue: `@laravel/echo-vue`. Livewire: `#[On('echo-private:tenants.{tenant.ulid},.invoice.paid')]`.
- Reload data with partial reloads on events instead of trusting broadcast payloads.

## Model broadcasting

`use BroadcastsEvents;` on models broadcasts created/updated/deleted automatically — handy, but be explicit about channels and data (`broadcastWith`).
