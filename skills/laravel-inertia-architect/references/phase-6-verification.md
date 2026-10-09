# Phase 6 — UI Verification & Browser Checking Protocol

The goal: prove each feature works in a real browser before calling it done. Do this yourself when you have a browser tool (Claude's built-in browser pane, Chrome extension, or Playwright via Pest), and give the user the same checklist.

## 1. Boot the environment

Local (Herd / native PHP):

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
composer run dev
```

`composer run dev` (starter kit script) runs `php artisan serve`, `php artisan queue:listen`, `php artisan pail` and `npm run dev` concurrently. Separately, if needed:

```bash
php artisan serve
npm run dev
php artisan queue:work
php artisan reverb:start --debug
```

Docker (Sail or project compose):

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm run dev
```

On Windows, run Sail inside WSL2; Herd for Windows or Docker Desktop also work. Note which one the user has.

SSR check: `npm run build:ssr` then `php artisan inertia:start-ssr` and view-source the page — the HTML should contain rendered markup, not just the root `<div id="app" data-page=…>`.

## 2. Manual / agent browser checklist (per feature)

Open the page and walk through the feature's acceptance criteria from the blueprint. For each page check:

**Console**
- [ ] No errors or React warnings (keys, hydration mismatch, act warnings).
- [ ] No CSP violations (or only expected ones in report-only mode).
- [ ] No 404s for assets.

**Network**
- [ ] First load returns HTML with the `data-page` JSON (or SSR markup).
- [ ] Subsequent navigations are XHR requests with `X-Inertia: true` and a JSON response containing `component`, `props`, `url`, `version`.
- [ ] Partial reloads send `X-Inertia-Partial-Data` / `X-Inertia-Partial-Component` and the response only contains the requested props.
- [ ] Deferred props arrive in a follow-up request.
- [ ] Form posts redirect with `303` after PUT/PATCH/DELETE; failed validation redirects back and the follow-up page response carries `props.errors` (Inertia convention — no raw 422 JSON for page forms).
- [ ] Props don't contain sensitive fields (password hashes, tokens, other tenants' data). Check the payload size on heavy pages.
- [ ] Asset version mismatch triggers a full reload (`409`) after deploy.

**React DevTools**
- [ ] The page component name matches the Inertia component string.
- [ ] Props and state are as expected; no unnecessary re-renders on drag (Profiler).

**UI / Tailwind**
- [ ] Layout at 375px (mobile), 768px (tablet), 1280px+ (desktop); no horizontal scroll.
- [ ] Dark mode.
- [ ] Keyboard: tab order, focus rings, Escape closes dialogs, dnd-kit keyboard sorting.
- [ ] Empty, loading (skeletons for deferred props), and error states.
- [ ] Validation errors render next to fields and are announced (aria-invalid / aria-describedby).

**Auth / permissions**
- [ ] A user without the permission doesn't see the button **and** gets 403 when calling the URL directly.
- [ ] Another workspace's resource URL returns 404/403.

Record what you checked and the result in `docs/progress.md`.

## 3. Automated browser tests

### Preferred: Pest browser testing (Playwright under the hood)

```bash
composer require pestphp/pest-plugin-browser --dev
npm install playwright@latest
npx playwright install chromium
```

See `assets/tests/Browser/KanbanBoardTest.php`. Typical test:

```php
it('moves a card between lists', function () {
    $user = User::factory()->withWorkspace()->create();
    [$todo, $done] = BoardList::factory()->count(2)->for($board = Board::factory()->for($user->currentWorkspace)->create())->create();
    $card = Card::factory()->for($todo, 'list')->create(['title' => 'Write tests']);

    $this->actingAs($user);

    visit(route('boards.show', [$user->currentWorkspace, $board]))
        ->assertSee('Write tests')
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs()
        ->drag("@card-{$card->ulid}", "@list-{$done->ulid}")
        ->assertSeeIn("@list-{$done->ulid}", 'Write tests');

    expect($card->fresh()->board_list_id)->toBe($done->id);
});

it('renders on mobile without overflow', function () {
    visit('/')->on()->mobile()->assertNoJavaScriptErrors();
});

it('has no smoke errors on main pages', function () {
    $this->actingAs(User::factory()->withWorkspace()->create());
    visit(['/dashboard', '/settings/profile'])->assertNoSmoke();
});
```

Use `data-test="card-…"` attributes (Pest's `@name` selector targets `data-test`/`dusk` attributes — confirm in docs) rather than CSS classes, so Tailwind refactors don't break tests. Check the current Pest docs for exact method names (`drag`, `on()->mobile()`, `assertNoSmoke`, screenshots, visual regression `assertScreenshotMatches`).

### Alternative: Laravel Dusk

Use when the project already has Dusk or the user prefers it.

```bash
composer require laravel/dusk --dev
php artisan dusk:install
php artisan dusk
```

```php
public function test_card_dialog_opens(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visitRoute('boards.show', [$this->workspace, $this->board])
            ->waitFor('@card-'.$this->card->ulid)
            ->click('@card-'.$this->card->ulid)
            ->waitForText('Description')
            ->assertQueryStringHas('card', $this->card->ulid)
            ->resize(375, 812)
            ->assertVisible('@card-dialog');
    });
}
```

### Server-side Inertia assertions (fast, run on every change)

```php
it('shows the board with its lists', function () {
    $this->actingAs($user = User::factory()->withWorkspace()->create());
    $board = Board::factory()->for($user->currentWorkspace)->hasLists(3)->create();

    $this->get(route('boards.show', [$user->currentWorkspace, $board]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('boards/show')
            ->has('lists', 3)
            ->where('board.name', $board->name)
            ->missing('board.workspace_id'));
});
```

### Test pyramid per epic

- Unit/feature tests for every Action and Policy (allowed / denied / cross-tenant).
- `assertInertia` tests for every page (component + required props).
- 1–3 browser tests per epic covering the primary workflows from the blueprint.
- `assertNoSmoke()` over all main routes.

Run: `./vendor/bin/pest --parallel`, browser tests with `./vendor/bin/pest tests/Browser` (add `--headed` to watch locally).
