---
name: laravel-api
description: Design and build REST and JSON:API endpoints in Laravel — routes/api.php, versioning, API Resources and Laravel 13 JSON:API resources, consistent error format, pagination, filtering/sorting with allowlists, Sanctum token auth and abilities, rate limiting, idempotency keys, webhooks (incoming and outgoing), OpenAPI docs with Scramble, and API tests. Use this whenever the user builds an API, mobile backend, integration endpoint, webhook receiver or public developer API in Laravel, or asks about API resources, versioning, API auth or API documentation.
---

# Laravel API

An API is a contract. Once someone integrates, breaking it costs them money — so make it consistent, versioned, documented and tested from the first endpoint.

**Inertia/Livewire apps don't need an internal JSON API** — page props are the API. Build `routes/api.php` only for real external consumers (mobile apps, partners, public API, webhooks).

## Setup

```bash
php artisan install:api              # Sanctum + routes/api.php
composer require dedoc/scramble      # OpenAPI docs generated from code (optional, recommended)
```

## Rules

- **API-01** Versioned from day one: `/api/v1/...` (route prefix + `App\Http\Controllers\Api\V1` namespace). Breaking changes → v2; additive changes allowed within a version.
- **API-02** Responses always go through **API Resources** (or L13 JSON:API resources) — never return models or arrays straight from Eloquent.
- **API-03** One error format for everything (validation, auth, not found, domain errors, 500s): `{ "message", "errors"?, "code"? }` or RFC 9457 problem details — chosen once, rendered in `bootstrap/app.php`.
- **API-04** Auth via Sanctum tokens with abilities (AUTH-07); every endpoint authorized with policies (SEC-10); tokens tenant-bound for multi-tenant apps.
- **API-05** Rate limited per token/user and per IP (`throttle:api`, plus stricter named limiters for expensive endpoints); return `Retry-After`.
- **API-06** Collections are paginated (cursor pagination for large/infinite feeds) with a max `per_page`; filters/sorts/includes allowlisted (`spatie/laravel-query-builder`).
- **API-07** Write endpoints that create money/side effects support an `Idempotency-Key` header.
- **API-08** Public identifiers are ULIDs/UUIDs; timestamps ISO-8601 UTC; money as integer minor units + currency.
- **API-09** HTTP semantics: `201` + `Location` on create, `204` on delete, `422` validation, `401` unauthenticated, `403` forbidden, `404` not found (also for cross-tenant), `409` conflicts, `429` throttled.
- **API-10** Every endpoint has feature tests (happy path, validation, unauthenticated, forbidden, cross-tenant) and appears in the generated docs.
- **API-11** Incoming webhooks verify signatures and are processed via queue idempotently (SEC-80); outgoing webhooks are signed, retried with backoff, and logged.
- **API-12** Deprecations announced with `Deprecation`/`Sunset` headers and changelog before removal.

## Patterns

### Routes

```php
// routes/api.php
Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::apiResource('invoices', Api\V1\InvoiceController::class);
    Route::post('invoices/{invoice}/send', Api\V1\SendInvoiceController::class)
        ->middleware('abilities:invoices:write')->name('invoices.send');
});
```

### Resource

```php
final class InvoiceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ulid,
            'number' => $this->number,
            'status' => $this->status->value,
            'total' => ['amount' => $this->total_cents, 'currency' => $this->currency],
            'issued_on' => $this->issued_on->toDateString(),
            'customer' => CustomerResource::make($this->whenLoaded('customer')),
            'lines_count' => $this->whenCounted('lines'),
            'links' => ['self' => route('api.v1.invoices.show', $this->resource)],
        ];
    }
}
```

Laravel 13 adds first-class JSON:API resources — use them when clients expect the JSON:API spec (check the 13.x docs for the class names).

### Controller

```php
final class InvoiceController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Invoice::class);

        $invoices = QueryBuilder::for(Invoice::class)
            ->allowedFilters(['status', AllowedFilter::exact('customer_id'), AllowedFilter::scope('overdue')])
            ->allowedSorts(['issued_on', 'number', 'total_cents'])
            ->allowedIncludes(['customer', 'lines'])
            ->defaultSort('-issued_on')
            ->cursorPaginate(min($request->integer('per_page', 25), 100));

        return InvoiceResource::collection($invoices);
    }

    public function store(StoreInvoiceRequest $request, CreateInvoice $createInvoice): JsonResponse
    {
        $invoice = $createInvoice->handle($request->user(), $request->customer(), $request->toData());

        return InvoiceResource::make($invoice)
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('api.v1.invoices.show', $invoice));
    }
}
```

### Error rendering

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->shouldRenderJsonWhen(fn (Request $r) => $r->is('api/*') || $r->expectsJson());

    $exceptions->render(fn (DomainException $e, Request $r) => $r->is('api/*')
        ? response()->json(['message' => $e->getMessage(), 'code' => class_basename($e)], 409)
        : null);
})
```

### Idempotency (API-07)

Middleware: read `Idempotency-Key`, lock with `Cache::lock("idem:{$user}:{$key}", 10)`, return the stored response for repeats (store status + body for 24h), reject same key with different payload hash (`422`).

### Webhooks

- Incoming: `spatie/laravel-webhook-client` (store → verify signature → queued `ProcessWebhookJob`) — or provider SDK verification (Stripe `Webhook::constructEvent`, Mollie: fetch the payment by id instead of trusting payload).
- Outgoing: `spatie/laravel-webhook-server` (HMAC signature header, retries, backoff); customers register endpoint URLs → validate (SEC-22 SSRF rules).

### Docs

Scramble generates OpenAPI from routes, FormRequests and Resources: `/docs/api`. Gate it in production (`Gate::define('viewApiDocs', ...)`) unless the API is public. Add PHPDoc descriptions on controller methods for better docs.

### Tests

```php
it('lists only the tenant\'s invoices', function () {
    $user = User::factory()->withTenant()->create();
    Invoice::factory()->count(3)->for($user->currentTenant)->create();
    Invoice::factory()->count(2)->create(); // other tenant

    Sanctum::actingAs($user, ['invoices:read']);

    $this->getJson('/api/v1/invoices')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'number', 'status', 'total' => ['amount', 'currency']]]]);
});
```
