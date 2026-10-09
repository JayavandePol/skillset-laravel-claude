# Optimistic UI, drag & drop, realtime

## Optimistic updates (v3)

Inertia v3 supports optimistic updates with automatic rollback for `router`, `useForm` and `useHttp`. Use them for low-risk, high-frequency actions: toggles, reordering, likes, checklist items. Check the v3 "Optimistic updates" docs page for the exact option names.

Pattern:
1. Apply the change to visible props immediately.
2. Send the request (`preserveScroll: true`, `only: [...]`).
3. On error Inertia rolls back; show a toast with the server message.

Don't use optimistic UI for payments, deletes of important data, or anything with server-side side effects the user must confirm.

## Drag & drop (kanban, sortable lists)

- React: `@dnd-kit/core` + `@dnd-kit/sortable` (keyboard sensor enabled for a11y). Vue: `vue-draggable-plus` / `@vueuse/integrations` sortable. Svelte: `svelte-dnd-action`.
- Server stores fractional positions (`decimal(20,10)`) so a move updates one row; an Action computes the position between neighbours and occasionally rebalances.
- Payload: `{ list: targetListUlid, after: previousCardUlid | null }` — never trust a client-sent numeric position blindly.

## Realtime with Reverb + Echo

```bash
php artisan install:broadcasting   # installs Reverb + Echo setup (choose Reverb)
npm install @laravel/echo-react    # or @laravel/echo-vue
```

```php
// Event
final class CardMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Card $card, public User $actor) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("boards.{$this->card->board->ulid}")];
    }

    public function broadcastWith(): array
    {
        return ['card' => $this->card->ulid, 'actor' => $this->actor->id];
    }
}

// routes/channels.php
Broadcast::channel('boards.{ulid}', fn (User $user, string $ulid) =>
    $user->can('view', Board::where('ulid', $ulid)->firstOrFail()));
```

```tsx
useEcho(`boards.${board.ulid}`, 'CardMoved', (e: { actor: number }) => {
  if (e.actor !== auth.user.id) router.reload({ only: ['lists'] });
});
```

- Broadcast minimal payloads (ids) and let clients partial-reload — avoids leaking data and keeps authorization on the server.
- `->toOthers()` / `broadcast(...)->toOthers()` to skip the sender.
- Presence channels for "who's viewing".
- Queue broadcasts (default `ShouldBroadcast` is queued); `ShouldBroadcastNow` only for tiny, latency-critical events.
