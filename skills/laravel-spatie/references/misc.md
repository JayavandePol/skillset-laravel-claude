# Other Spatie packages

## laravel-honeypot (SEC-71)

```bash
composer require spatie/laravel-honeypot
```

Blade: `<x-honeypot />` in public forms + `ProtectAgainstSpam` middleware. Inertia/SPA: share `Honeypot` data as a prop and render the fields (see README "Usage in Inertia"). Combine with rate limiting; add Cloudflare Turnstile for high-value forms.

## laravel-model-states

State machines with transition classes:

```php
abstract class InvoiceState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Sent::class, SendInvoiceTransition::class)
            ->allowTransition(Sent::class, Paid::class)
            ->allowTransition([Draft::class, Sent::class], Cancelled::class);
    }
}
```

Use when transitions carry logic/side effects; a simple enum with `canTransitionTo()` is enough otherwise.

## laravel-webhook-client / laravel-webhook-server (SPA-09)

- Client: route `Route::webhooks('webhooks/stripe', 'stripe')`, config per provider with `signature_validator`, `webhook_profile`, `process_webhook_job` (queued, idempotent). Exclude the route from CSRF.
- Server: `WebhookCall::create()->url($endpoint->url)->payload($payload)->useSecret($endpoint->secret)->dispatch();` — retries with backoff, signature header, events for failure handling. Validate customer endpoint URLs (SSRF).

## laravel-pdf

```php
Pdf::view('pdf.invoice', ['invoice' => $invoice])
    ->format(Format::A4)
    ->name("invoice-{$invoice->number}.pdf")
    ->save($path);   // or ->download()
```

Uses a headless Chrome driver (Browsershot) by default — needs Chromium in the image; check the README for alternative drivers. Generate in queued jobs; store the PDF via Media Library for invoices (legal archive).

## laravel-responsecache

Caches full responses for anonymous public pages (marketing, docs). Never cache authenticated/personalised pages; clear on content changes (`ResponseCache::clear()` or tags).

## laravel-image-optimizer

Optimises images on upload/conversion; Media Library integrates it automatically when the binaries exist.

## Others worth knowing

- `laravel-sitemap` — SEO sitemaps for public pages.
- `laravel-cookie-consent` — only if you set non-essential cookies.
- `laravel-route-attributes` — attribute routing (pick one routing style per project).
- `laravel-event-sourcing` — only for domains that truly need an event-sourced model (finance ledgers); significant complexity.
- `laravel-markdown` — Markdown rendering with highlighting (configure safe HTML handling, SEC-20).
