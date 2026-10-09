---
name: laravel-livewire
description: Build Laravel UIs with Livewire 4, Flux UI, Alpine.js and Blade — single-file components, islands, pages/layouts structure, form objects, validation, security of public properties (#[Locked], authorization per action), computed properties, lazy loading, polling, file uploads, events, testing, and upgrading Livewire 3/Volt apps. Use this whenever the user works with Livewire or Blade components, wire:model, Flux, Alpine, Volt, the Livewire starter kit, or Filament custom pages/components (Filament runs on Livewire).
---

# Laravel Livewire

Livewire 4 (Jan 2026) makes single-file components (PHP + Blade + scoped CSS/JS in one file) the default, adds `@island` for independently re-rendering regions, and introduces `components/`, `pages/`, `layouts/` directories. Filament v5 runs on it. Check https://livewire.laravel.com/docs for current syntax before writing components.

The key mental model: **every public property and every action is an HTTP endpoint the user can call with any value.** Write components as if they were controllers.

## Rules

- **LW-01** Components stay thin like controllers: validate, authorize, call an Action, update state. No business logic in components.
- **LW-02** Every action method authorizes (`$this->authorize()`) — mount-time checks alone are not enough.
- **LW-03** Public properties holding IDs, prices, roles or anything the user mustn't change are `#[Locked]`; prefer passing models (Livewire locks model ids) and re-fetch with scoping.
- **LW-04** Validation with `#[Validate]` attributes or Form objects (`Livewire\Form`) and `$this->validate()` on submit — rules mirror FormRequest standards (SEC-01).
- **LW-05** Expensive data via `#[Computed]` (cached per request; `persist`/`cache` options deliberately), lazy components (`#[Lazy]`/`lazy` attribute) or islands — not in `render()` on every request.
- **LW-06** Lists paginate (`WithPagination`) and eager load; never store large collections in public properties (they're serialized to the client).
- **LW-07** `wire:model` defaults to deferred; use `.live` (debounced) only where instant feedback is needed.
- **LW-08** File uploads (`WithFileUploads`) validate type/size and are moved to private storage in the Action (SEC-50).
- **LW-09** Use Flux (or the project's component library) consistently; accessible labels and focus handling.
- **LW-10** Tests use `Livewire::test()` for every component action, including unauthorized calls.

## Structure (Livewire 4)

```
resources/views/
├── components/          # ⚡ single-file Livewire components + anonymous Blade components
│   └── invoices/⚡invoice-table.blade.php
├── pages/               # full-page components routed with Route::livewire()
│   └── invoices/⚡index.blade.php
└── layouts/             # app.blade.php, auth.blade.php
app/Livewire/Forms/      # Form objects (if using class-based forms)
```

Use multi-file (class + view) for large components; keep logic in Actions either way.

## Example (single-file)

```php
<?php

use App\Actions\Invoices\SendInvoice;
use App\Models\Invoice;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public int $invoiceId;

    public string $message = '';

    #[Computed]
    public function invoice(): Invoice
    {
        return Invoice::with('customer:id,name,email')->findOrFail($this->invoiceId);
    }

    public function send(SendInvoice $sendInvoice): void
    {
        $this->authorize('send', $this->invoice);
        $this->validate(['message' => ['nullable', 'string', 'max:2000']]);

        $sendInvoice->handle(auth()->user(), $this->invoice, $this->message);

        $this->dispatch('invoice-sent');
        Flux::toast(__('Invoice sent.')); // toast is a Flux Pro component — use your own toast event otherwise
    }
};
?>

<div>
    <flux:heading>{{ $this->invoice->number }}</flux:heading>
    <flux:textarea wire:model="message" :label="__('Message')" />
    <flux:button wire:click="send" variant="primary">{{ __('Send') }}</flux:button>
</div>
```

Check the exact single-file syntax (`new class extends Component` header, `⚡` filename prefix) in the Livewire 4 docs; it may differ by minor version.

## Islands, lazy, polling

```blade
@island(lazy: true)
    <livewire:invoices.revenue-chart />
@endisland

<div wire:poll.15s="refreshStatus">…</div>
```

Polling no longer blocks other requests in v4; still poll only what's needed.

## Security notes

- `#[Locked]` for ids; never accept `price`, `role`, `tenant_id` from public props.
- Use `$this->authorize()` in each action; `mount()` authorization for initial view.
- Livewire endpoints are CSRF-protected; don't disable.
- Alpine + CSP: standard Alpine needs `unsafe-eval` — use Livewire's CSP-safe mode/Alpine CSP build if enforcing strict CSP (check docs).

## Upgrading from Livewire 3 / Volt

- v3 class components keep working; migrate gradually to single-file where it helps.
- Volt functional/class components → Livewire 4 single-file is the natural successor; check the upgrade guide for Volt status.
- Run `php artisan livewire:upgrade` if available and review the diff.

## Testing

```php
Livewire::actingAs($user)
    ->test('invoices.invoice-actions', ['invoiceId' => $invoice->id])
    ->set('message', 'Thanks!')
    ->call('send')
    ->assertDispatched('invoice-sent')
    ->assertHasNoErrors();

Livewire::actingAs($outsider)
    ->test('invoices.invoice-actions', ['invoiceId' => $invoice->id])
    ->call('send')
    ->assertForbidden();
```
