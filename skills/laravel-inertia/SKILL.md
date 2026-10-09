---
name: laravel-inertia
description: Build Laravel frontends with Inertia.js v3 and React 19, Vue 3 or Svelte 5 plus TypeScript and Tailwind v4 — page/layout/component structure, typed props and Wayfinder routes, shared data, state management, partial reloads, deferred/optional/merge props, prefetching, infinite scroll, optimistic updates, forms (useForm/Form, React Hook Form + Zod, Precognition), error pages, SSR and code splitting. Use this whenever the user works on resources/js in a Laravel app, creates Inertia pages or components, asks about React/Vue/Svelte with Laravel, shadcn/ui in Laravel, SSR, "partial reload", Inertia forms or validation errors, or upgrades Inertia v1/v2 to v3.
---

# Laravel + Inertia

Inertia lets the server own routing, auth and data while the client renders a modern SPA. The rules below keep that division clean: the server decides *what* the user may see and do, the client decides *how* it looks.

Inertia v3 (2026) changed setup and APIs: React 19+/Node 24+, ESM-only, `@inertiajs/vite` plugin, built-in HTTP client (Axios optional), `useHttp`, optimistic updates, layout props. Tutorials for v1/v2 are often wrong now — check https://inertiajs.com/docs/v3 for any API you're unsure of.

## Rules

- **FE-01** Pages are thin: receive typed props, compose layout + feature components. Business logic stays on the server.
- **FE-02** Every page has a typed props interface; shared data typed globally (`SharedData`). DTO types generated from PHP (laravel-data + typescript-transformer) or kept in `types/`.
- **FE-03** No hard-coded URLs: use Wayfinder (`@/actions/...`, `@/routes/...`) or Ziggy in legacy apps.
- **FE-04** Props contain only what the page renders — use Resources/DTOs, never raw models with all columns (leaks data, bloats payloads).
- **FE-05** Shared data is small and cheap; expensive shared values are closures. Permission flags for the UI come from policies (`can`), never computed on the client.
- **FE-06** Heavy or below-the-fold data uses deferred/optional props and partial reloads; lists use pagination/infinite scroll with merge props.
- **FE-07** Forms submit through Inertia (`useForm`/`<Form>`/router) so CSRF, errors and redirects work; server validation errors are always displayed per field.
- **FE-08** Mutations redirect (303) back or to a page; don't return JSON from web routes for Inertia requests. `useHttp` only for non-navigational calls (autosave, typeahead).
- **FE-09** SSR-safe code: no `window`/`document`/`localStorage` during render or module init.
- **FE-10** Accessible components (labels, focus management, keyboard support — shadcn/Radix/Reka/Bits primitives), dark mode support, responsive at 375px.
- **FE-11** TypeScript strict, `npm run types` and `npm run lint` clean; no `any` without a comment.
- **FE-12** Error pages for 403/404/419/500/503 rendered via Inertia; 419 triggers a friendly reload.

## References

| Topic | File |
|---|---|
| Setup, directory structure, page/layout patterns | `references/structure.md` |
| Shared data, state hierarchy, partial reloads, deferred/merge props, prefetch, polling | `references/data-and-state.md` |
| Forms & validation (useForm, Form, RHF + Zod, Precognition, uploads) | `references/forms.md` |
| Optimistic UI, drag & drop, realtime with Echo/Reverb | `references/optimistic-realtime.md` |
| SSR, code splitting, performance | `references/ssr-performance.md` |
| Framework specifics: React / Vue / Svelte | `references/frameworks.md` |

For verifying pages in a browser (network tab, React/Vue DevTools, console) see `laravel-testing` → browser verification.
