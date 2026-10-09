# Billing: Cashier (Stripe / Paddle) & Mollie

## Choosing

| Provider | Package | Notes |
|---|---|---|
| Stripe | `laravel/cashier` | subscriptions, metered billing, Checkout, customer portal, tax |
| Paddle | `laravel/cashier-paddle` | merchant of record — handles EU VAT/sales tax for you |
| Mollie | `mollie/laravel-mollie` (+ community `mollie/laravel-cashier-mollie`) | iDEAL, Bancontact, SEPA — preferred by NL/BE customers |

## Rules (ECO-07)

- Webhooks are the source of truth for payment/subscription state; the success redirect only shows "processing".
- Verify webhooks (Cashier verifies Stripe signatures when `STRIPE_WEBHOOK_SECRET` is set; Mollie: re-fetch the payment by id from the API).
- Idempotent webhook handling (store processed event ids).
- Plans/prices in config or DB; never accept amounts from the client.
- Billable model is usually the **tenant/workspace**, not the user.
- Map plans → features/limits (Pennant or a `Plan` enum) and enforce limits in policies/Actions.
- Invoices: store PDFs, numbering per year, VAT per line; Belgian B2B e-invoicing (Peppol) from 2026 if you issue invoices yourself.

## Cashier (Stripe) quick start

```bash
composer require laravel/cashier
php artisan vendor:publish --tag="cashier-migrations"
php artisan migrate
```

```php
final class Workspace extends Model
{
    use Billable;
}

// Checkout
return $workspace->newSubscription('default', config('billing.plans.pro.stripe_price'))
    ->trialDays(14)
    ->checkout(['success_url' => route('billing.success'), 'cancel_url' => route('billing')]);

// Gate
$workspace->subscribed('default');
$workspace->subscription('default')->onGracePeriod();
```

Customer portal: `$workspace->redirectToBillingPortal(route('billing'))`. Listen to `WebhookReceived`/`WebhookHandled` for custom logic.

## Mollie (one-off payments)

```php
$payment = Mollie::api()->payments->create([
    'amount' => ['currency' => 'EUR', 'value' => Money::ofMinor($order->total_cents, 'EUR')->getAmount()->toScale(2)->__toString()],
    'description' => "Order {$order->number}",
    'redirectUrl' => route('orders.show', $order),
    'webhookUrl' => route('webhooks.mollie'),
    'metadata' => ['order_id' => $order->ulid],
]);
$order->update(['mollie_payment_id' => $payment->id]);
return redirect($payment->getCheckoutUrl(), 303);
```

Webhook: `POST` with `id` → fetch payment from API → update order in an Action → idempotent.

## Testing

Stripe test mode keys + Stripe CLI (`stripe listen --forward-to localhost/stripe/webhook`); Mollie test API key. Unit-test webhook handlers with recorded payloads; fake the gateway service interface in feature tests.
