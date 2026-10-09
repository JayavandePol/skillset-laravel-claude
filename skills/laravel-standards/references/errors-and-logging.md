# Errors & logging

## Domain exceptions

```php
final class InvoiceAlreadyPaid extends DomainException
{
    public static function for(Invoice $invoice): self
    {
        return new self("Invoice {$invoice->number} is already paid.");
    }
}
```

Render centrally:

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(fn (InvoiceAlreadyPaid $e, Request $request) =>
        back()->with('error', __('This invoice has already been paid.')));

    $exceptions->dontReport([InvoiceAlreadyPaid::class]);

    $exceptions->context(fn () => ['tenant_id' => app(CurrentTenant::class)->id()]);
})
```

For Inertia apps also map 403/404/419/500/503 to an error page component (see `laravel-inertia`); for APIs return RFC 9457 problem-details style JSON (see `laravel-api`).

## Validation errors

Thrown by FormRequests; don't catch them. For validation inside Actions, throw `ValidationException::withMessages([...])`.

## Logging

- Channel: `stack` locally, `stderr` (JSON formatter) in containers so the platform collects logs.
- Always context arrays; add shared context with `Log::withContext()` / `Context::add()` (Laravel 11+ Context facade) in middleware: request id, user id, tenant id.
- Levels: `debug` (local noise), `info` (business events), `warning` (recoverable/unexpected), `error` (needs attention), `critical` (paging).
- Never log: passwords, tokens, API keys, session ids, full payment data, BSN/ID numbers, health data. Redact request bodies.
- Error tracking: Sentry, Flare or Nightwatch in production; ignore noisy expected exceptions.

## Retries & external calls

- `Http::retry(3, 200, throw: false)->timeout(10)` for outgoing HTTP; always set timeouts.
- Wrap SDK exceptions in your own (`PaymentFailed`) inside the Service so callers don't depend on vendor types.
