# Phase 1 — Core Architecture & Routing

## Scaffolding

```bash
# Laravel installer (composer global require laravel/installer)
laravel new my-app --react --pest
# or: composer create-project laravel/react-starter-kit my-app
cd my-app
composer require --dev larastan/larastan laravel/pint
php artisan install:api        # only if a public/mobile API is in scope (adds Sanctum + routes/api.php)
composer run dev                # serve + queue + logs + vite together
```

Check the starter kit's current `composer.json`/`package.json` after scaffolding — it decides the exact Inertia/React/Tailwind versions.

## Layering: where code goes

```
app/
├── Actions/            # one business operation each: CreateBoard, MoveCard, InviteMember
│   └── Cards/MoveCard.php
├── Data/               # DTOs (spatie/laravel-data or plain readonly classes)
├── Enums/              # backed enums: WorkspaceRole, CardStatus, Permission
├── Events/ Listeners/  # domain events (CardMoved) → broadcasting, activity log, notifications
├── Http/
│   ├── Controllers/    # thin: authorize → validate (FormRequest) → call Action → respond
│   ├── Middleware/     # SetCurrentWorkspace, EnsureWorkspaceMember
│   └── Requests/       # FormRequests per write endpoint
├── Jobs/
├── Models/
│   └── Concerns/BelongsToWorkspace.php
├── Policies/
├── Queries/            # read-side query objects for complex lists/reports
├── Services/           # integrations & cross-cutting logic: Billing, Mollie, PdfRenderer
└── Support/            # CurrentWorkspace, helpers
```

**Why this split:**

- **Actions** hold the write-side business logic. They are reused by controllers, Filament, jobs, console commands and tests — so logic never lives in a controller or a Filament resource. One public `handle()` method, dependencies injected via constructor, wrap multi-step writes in `DB::transaction()`.
- **Services** wrap external systems or stateful cross-cutting concerns (payment provider, PDF rendering, AI SDK). Bind an interface in a service provider when you'll want to fake it in tests.
- **Repositories — use sparingly.** Eloquent already is a data-access layer; a repository per model mostly adds indirection. Use **query objects** (`app/Queries`) for complex, reused reads (dashboards, filtered lists, reports) and only introduce a repository interface when there's a real second implementation (e.g. external API vs. local DB). Tell the user this trade-off when they ask for "Repository pattern".
- **Controllers** stay under ~15 lines per method.

### Action template

See `assets/app/Actions/Cards/MoveCard.php`. Shape:

```php
final class MoveCard
{
    public function __construct(private CardPositioner $positioner) {}

    public function handle(User $actor, Card $card, BoardList $target, ?Card $after): Card
    {
        return DB::transaction(function () use ($actor, $card, $target, $after) {
            $card->board_list_id = $target->id;
            $card->position = $this->positioner->between($target, $after);
            $card->save();

            CardMoved::dispatch($card, $actor);

            return $card;
        });
    }
}
```

### Thin controller

```php
final class CardMoveController
{
    public function __invoke(MoveCardRequest $request, Card $card, MoveCard $moveCard): RedirectResponse
    {
        $moveCard->handle(
            $request->user(),
            $card,
            $request->targetList(),
            $request->afterCard(),
        );

        return back();
    }
}
```

Authorization happens in `MoveCardRequest::authorize()` (via the policy) or with `Gate::authorize()` / `#[Authorize]`-style attributes if the Laravel version supports them — check the docs.

## Routing

| File | Purpose | Middleware |
|---|---|---|
| `routes/web.php` | Inertia pages and form posts (session auth, CSRF) | `web`, `auth`, `verified`, `workspace` |
| `routes/api.php` | Public/mobile/3rd-party API only (Sanctum tokens, JSON:API resources in L13) | `api`, `auth:sanctum`, `throttle:api` |
| `routes/channels.php` | Broadcast channel authorization | |
| `routes/console.php` | Scheduled tasks | |
| Filament | Registered by the panel provider at `/admin` | panel's own auth middleware |

Don't build a JSON API for your own React pages — Inertia props *are* the API. Only add `routes/api.php` for real external consumers.

Workspace-scoped URLs keep tenancy explicit and bookmarkable:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('w/{workspace:slug}')
        ->middleware(['workspace'])          // alias for SetCurrentWorkspace + EnsureWorkspaceMember
        ->scopeBindings()                   // child bindings must belong to parent
        ->group(function () {
            Route::resource('boards', BoardController::class);
            Route::post('cards/{card:ulid}/move', CardMoveController::class)->name('cards.move');
        });
});
```

### Middleware pipeline (bootstrap/app.php)

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        HandleInertiaRequests::class,
        AddLinkHeadersForPreloadedAssets::class,
        AddContentSecurityPolicyHeaders::class, // Phase 4
    ]);

    $middleware->alias([
        'workspace' => SetCurrentWorkspace::class,
    ]);

    $middleware->trustProxies(at: '*'); // behind Dokploy/Traefik or Nginx Proxy Manager — restrict to proxy IPs if known
})
```

`SetCurrentWorkspace` resolves the route's workspace, aborts 403/404 for non-members, stores it in a scoped `CurrentWorkspace` singleton and calls `setPermissionsTeamId($workspace->id)` for Spatie teams (Phase 5).

## Exception & response conventions

- Render 403/404/419/500 as Inertia error pages in `bootstrap/app.php` `withExceptions()` → `respond()` (Inertia docs: "Error handling").
- Flash toasts via `Inertia::flash()` / `->with('success', …)` shared in `HandleInertiaRequests` (or `router.flash()` client-side in v3).
- `419` (expired CSRF): reload the page with a friendly toast instead of an ugly error.

## Quality gates to set up now

- `phpstan.neon` with Larastan at level 6+ (raise over time).
- `pint.json` (Laravel preset), `npm run lint` / `npm run types` from the starter kit.
- Pest arch tests (`tests/Arch.php`): controllers don't use `DB` directly, Actions are `final`, no `dd()`/`dump()`, models only in `App\Models`.

```php
arch()->preset()->laravel();
arch()->preset()->security();
arch('actions are final')->expect('App\Actions')->toBeFinal();
arch('no debugging')->expect(['dd', 'dump', 'ray'])->not->toBeUsed();
```
