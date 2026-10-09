# Setup & structure

## New project

Use the official starter kit (`laravel new app --react|--vue|--svelte`) — see `laravel-auth/references/starter-kits.md`. It wires Inertia, TypeScript, Tailwind v4, shadcn (React/Vue/Svelte ports), Wayfinder and Pest.

Existing Laravel app: `composer require inertiajs/inertia-laravel`, `php artisan inertia:middleware`, `npm install @inertiajs/react` (or vue3/svelte) + `@inertiajs/vite`, configure per the v3 "Server-side setup" and "Client-side setup" docs.

## Directory structure

```
resources/js/
├── app.tsx                  # createInertiaApp (page resolution via @inertiajs/vite)
├── ssr.tsx                  # SSR entry
├── pages/                   # one file per Inertia page; path mirrors Inertia::render('invoices/show')
│   ├── dashboard.tsx
│   ├── invoices/{index,show,create,edit}.tsx
│   ├── settings/…
│   └── errors/error-page.tsx
├── layouts/                 # app-layout, auth-layout, settings-layout
├── components/
│   ├── ui/                  # generated shadcn primitives — keep close to upstream
│   └── …                    # app-wide composed components (app-sidebar, data-table, page-header)
├── features/<domain>/       # domain components & hooks per epic (invoices/invoice-form.tsx, use-invoice-totals.ts)
├── hooks/                   # generic hooks (use-appearance, use-debounce, use-mobile)
├── lib/                     # utils, formatters (money, dates), zod schemas
├── types/
│   ├── index.d.ts           # SharedData, PageProps augmentation, User
│   └── generated.d.ts       # from spatie/laravel-typescript-transformer (if used)
├── actions/ routes/ wayfinder/   # Wayfinder output
└── i18n/                    # translations, if multi-language
```

`features/` keeps domain UI together; `components/` holds only cross-domain building blocks.

## Page pattern

```php
// Controller
public function show(Invoice $invoice): Response
{
    Gate::authorize('view', $invoice);

    return Inertia::render('invoices/show', [
        'invoice' => InvoiceData::from($invoice->load('lines', 'customer:id,name')),
        'can' => [
            'update' => request()->user()->can('update', $invoice),
            'send' => request()->user()->can('send', $invoice),
        ],
        'payments' => Inertia::defer(fn () => PaymentData::collect($invoice->payments()->latest()->get())),
    ]);
}
```

```tsx
// pages/invoices/show.tsx
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { InvoiceSummary } from '@/features/invoices/invoice-summary';
import type { InvoiceData, PaymentData } from '@/types/generated';

interface Props {
  invoice: InvoiceData;
  can: { update: boolean; send: boolean };
  payments?: PaymentData[];
}

export default function InvoiceShow({ invoice, can, payments }: Props) {
  return (
    <AppLayout breadcrumbs={[{ title: 'Invoices', href: index().url }, { title: invoice.number, href: show(invoice).url }]}>
      <Head title={invoice.number} />
      <InvoiceSummary invoice={invoice} can={can} payments={payments} />
    </AppLayout>
  );
}
```

## Layouts

- Persistent layouts keep sidebar state and scroll between navigations (`Page.layout = (page) => <AppLayout>{page}</AppLayout>` in React, or the default-layout option in `createInertiaApp`).
- v3 **layout props** pass data from a page to its persistent layout (breadcrumbs, actions) without context hacks.

## Error pages (FE-12)

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
        if (! app()->environment('local') && in_array($response->getStatusCode(), [403, 404, 500, 503], true)) {
            return Inertia::render('errors/error-page', ['status' => $response->getStatusCode()])
                ->toResponse($request)
                ->setStatusCode($response->getStatusCode());
        }

        if ($response->getStatusCode() === 419) {
            return back()->with('error', __('The page expired, please try again.'));
        }

        return $response;
    });
})
```
