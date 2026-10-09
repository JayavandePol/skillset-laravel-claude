# Research Protocol

Run this before Phase 0 and again whenever you touch a package for the first time in a session. Keep it quick (a handful of searches/fetches), but don't skip it.

## 1. Version check

Find the current stable major for each piece of the stack and note it in `docs/progress.md`:

| Package | Where to check | Known baseline (Oct 2026 — re-verify) |
|---|---|---|
| Laravel | https://laravel.com/docs/master/releases | 13.x (released Mar 2026), PHP 8.3–8.5, bug fixes to Q3 2027, security to Q1 2028 |
| Inertia.js | https://inertiajs.com/docs/v3/getting-started/upgrade-guide | v3 (Apr 2026): React 19+, Node 24+, ESM-only, built-in HTTP client (Axios optional), `useHttp`, optimistic updates, layout props, `@inertiajs/vite` |
| Filament | https://filamentphp.com/docs | v5: Livewire 4, Tailwind 4, PHP 8.2+, Laravel 11.28+ |
| spatie/laravel-permission | https://spatie.be/docs/laravel-permission | v7 (Laravel 12–13, PHP 8.3+); v8 exists — check its prerequisites page and upgrade notes (enum support in `findByName`/`findOrCreate`) |
| spatie/laravel-medialibrary | https://spatie.be/docs/laravel-medialibrary | v11 — Laravel 13 support from 11.21.0 |
| Pest | https://pestphp.com/docs | v4 (browser testing via Playwright); v5 may be current — check |
| Laravel starter kits | https://laravel.com/docs/starter-kits | React kit: Inertia + TS + Tailwind + shadcn/ui + Wayfinder |

Useful quick commands inside an existing project:

```bash
composer show laravel/framework inertiajs/inertia-laravel filament/filament spatie/laravel-permission spatie/laravel-medialibrary
npm ls @inertiajs/react react @inertiajs/vite tailwindcss
php -v
```

## 2. Docs to read per phase

Fetch the specific page for what you're about to build — not the whole site.

- **Phase 1:** Laravel `routing`, `middleware`, `container`, `releases` (for new attributes-based config in 13.x).
- **Phase 2:** `migrations`, `eloquent-relationships`, `eloquent#preventing-lazy-loading`, `queries`.
- **Phase 3:** Inertia `pages`, `layouts`, `shared-data`, `partial-reloads`, `deferred-props`, `forms`, `server-side-rendering`, `optimistic-updates`; Laravel `vite`; Wayfinder README.
- **Phase 4:** Laravel `validation`, `authorization`, `csrf`, `rate-limiting`, `precognition`; Inertia `validation`, `csrf-protection`; spatie/laravel-csp README.
- **Phase 5:** Filament `panel-configuration`, `resources`, `users` (access control); Spatie permission `teams-permissions`, `using-policies`; medialibrary `defining-conversions`, `responsive-images`.
- **Phase 6:** Pest `browser-testing`; Laravel `dusk` if the project uses Dusk; Inertia `testing`.
- **Phase 7:** Laravel `deployment`, `queues`, `horizon`, `octane` (if used), `reverb`; Dokploy docs; serversideup/php docs.

## 3. SaaS teardown (for "remake"/"clone" requests)

Goal: understand the product well enough to design its data model — not to copy its branding, copy, or proprietary assets. The remake gets its own name and visual identity.

Research, in this order:

1. **Official product pages & pricing** — list features per pricing tier; tiers reveal which features are core vs. premium (great for MVP scoping).
2. **Help center / knowledge base** — the best source for real workflows, entity names and edge cases (e.g. Trello: boards → lists → cards → checklists, labels, power-ups, butler automations; Teamleader: contacts/companies → deals → quotations → invoices, projects, time tracking, calendar).
3. **Public API docs** — reveal the actual data model: entities, fields, enums, relationships, webhooks. This is the single most valuable source for the ERD.
4. **Changelog / release notes** — what's recent, what users asked for.
5. **Reviews (G2, Capterra, Reddit)** — top complaints = differentiation opportunities for the remake.

Produce, per platform:

- Core entities and how they relate (feeds the ERD).
- The 3–5 primary user workflows, step by step (feeds the epics and the browser tests).
- Roles & permissions model (feeds Spatie teams setup).
- Integrations/webhooks that matter.
- MVP vs. later: what a v1 needs to be useful.

Cite the URLs used in the blueprint's "Sources" section.
