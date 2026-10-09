# Data sharing & state

## State hierarchy (first choice → last)

1. **Server props** — the source of truth. After a mutation, redirect; Inertia updates props.
2. **Shared data** — `HandleInertiaRequests::share()`: auth user, current tenant, UI permission flags, flash, locale, feature flags.
3. **URL state** — filters, sort, tabs, selected record (`?invoice=01J…`) via `router.get(url, query, { preserveState: true, replace: true })`. Shareable, survives refresh.
4. **Remembered local state** — `useRemember` for drafts/tabs restored on back navigation.
5. **Layout props (v3)** — page → persistent layout communication.
6. **Client store (React context, Zustand, Pinia, Svelte stores)** — only for client-only cross-tree state (command palette, drag session, media player). Never mirror server data.

## Shared data (FE-05)

```php
public function share(Request $request): array
{
    $user = $request->user();
    $tenant = app(CurrentTenant::class)->get();

    return [
        ...parent::share($request),
        'name' => config('app.name'),
        'auth' => ['user' => $user ? UserData::from($user) : null],
        'tenant' => $tenant?->only(['ulid', 'name', 'slug']),
        'can' => fn () => $user && $tenant ? [
            'manageMembers' => $user->can('manageMembers', $tenant),
            'viewBilling' => $user->can('viewBilling', $tenant),
        ] : [],
        'flash' => fn () => [
            'success' => $request->session()->get('success'),
            'error' => $request->session()->get('error'),
        ],
        'locale' => app()->getLocale(),
        'features' => fn () => $tenant ? Feature::for($tenant)->values(['new-dashboard', 'ai-summaries']) : [],
    ];
}
```

Type it once:

```ts
// types/index.d.ts
export interface SharedData {
  name: string;
  auth: { user: User | null };
  tenant: { ulid: string; name: string; slug: string } | null;
  can: Record<string, boolean>;
  flash: { success?: string; error?: string };
  locale: string;
  features: Record<string, boolean>;
  [key: string]: unknown;
}
// usePage<SharedData>()
```

## Prop types on the server

```php
return Inertia::render('invoices/index', [
    'filters' => $request->only(['search', 'status']),
    'invoices' => fn () => InvoiceListData::collect($query->paginate(25)->withQueryString()),  // lazy: evaluated if included
    'stats' => Inertia::defer(fn () => $statsQuery->get()),                                     // loads after first render
    'customers' => Inertia::optional(fn () => CustomerOptionData::collect(...)),                // only on explicit partial reload
    'activity' => Inertia::merge(fn () => $activity->cursorPaginate(20)),                        // appended on reload
    'meta' => Inertia::always(fn () => [...]),                                                   // included even in partial reloads
]);
```

Check current names (`optional` vs older `lazy`, `Inertia::scroll()` for infinite scroll, `deepMerge`, `once`) in the v3 docs.

## Client-side loading patterns

```tsx
router.reload({ only: ['invoices'] });                          // partial reload
router.get(index().url, { search }, { preserveState: true, replace: true, only: ['invoices'] }); // filter
<Link href={show(invoice).url} prefetch>…</Link>                // prefetch on hover
<Deferred data="stats" fallback={<StatsSkeleton />}><Stats /></Deferred>
<WhenVisible data="customers" fallback={<Spinner />}>…</WhenVisible>
usePoll(10_000, { only: ['jobStatus'] });                       // polling
<InfiniteScroll data="activity">…</InfiniteScroll>              // v2.2+/v3 infinite scroll component — check docs
```

## Flash messages

Shared `flash` + a toast component reading it on every page change. v3 also has `router.flash()` for client-set flash.
