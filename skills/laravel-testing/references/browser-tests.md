# Browser tests

## Pest browser plugin (preferred)

```bash
composer require pestphp/pest-plugin-browser --dev
npm install playwright@latest --save-dev
npx playwright install chromium
```

```php
it('creates an invoice end to end', function () {
    $user = actingAsTenantUser();
    $customer = Customer::factory()->for($user->currentTenant)->create(['name' => 'Acme BV']);

    visit(route('invoices.create', $customer))
        ->assertSee('New invoice')
        ->fill('lines[0][description]', 'Consultancy')
        ->fill('lines[0][quantity]', '8')
        ->fill('lines[0][unit_price]', '9500')
        ->press('Save')
        ->assertSee('Invoice created')
        ->assertNoJavaScriptErrors();

    expect(Invoice::count())->toBe(1);
});

it('has no smoke errors on main pages', function () {
    actingAsTenantUser();
    visit(['/dashboard', '/invoices', '/customers', '/settings/profile'])->assertNoSmoke();
});

it('is usable on mobile', function () {
    actingAsTenantUser();
    visit('/invoices')->on()->mobile()->assertSee('Invoices')->assertNoJavaScriptErrors();
});

it('supports dark mode', function () {
    actingAsTenantUser();
    visit('/dashboard')->inDarkMode()->assertNoJavaScriptErrors();
});
```

- Selectors: prefer visible text, labels, and `data-test="…"` attributes (Pest supports an `@name` shorthand — check docs for which attribute it targets) over CSS classes so Tailwind changes don't break tests.
- Visual regression: `->assertScreenshotMatches()` for key layouts (commit baselines; review diffs).
- Accessibility: Pest browser has accessibility assertions in recent versions (e.g. `assertNoAccessibilityIssues`) — check docs.
- Debug: `->debug()`, `--headed`, screenshots on failure in `tests/Browser/Screenshots`.
- Check the Pest browser docs for exact method names in the installed version (`drag`, `on()->mobile()`, `inDarkMode()`, `assertNoSmoke`).

## Laravel Dusk (existing suites)

```bash
composer require laravel/dusk --dev
php artisan dusk:install
php artisan dusk
```

```php
public function test_invoice_dialog(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visitRoute('invoices.index')
            ->waitForText('Invoices')
            ->click('@new-invoice')
            ->waitFor('@invoice-form')
            ->type('@line-description-0', 'Consultancy')
            ->press('Save')
            ->waitForText('Invoice created')
            ->resize(375, 812)
            ->assertVisible('@mobile-nav');
    });
}
```

Use `dusk="..."` attributes for `@` selectors. New suites → Pest browser; keep Dusk where it already works well.

## What to cover (TST-04)

Per epic: the primary workflows from the blueprint's acceptance criteria. Plus: login, 2FA challenge, permission-denied UI, file upload, one realtime update if Reverb is used.
