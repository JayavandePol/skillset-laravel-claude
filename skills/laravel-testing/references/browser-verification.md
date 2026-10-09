# Boot & browser verification protocol (TST-10)

## 1. Boot the environment

```bash
composer install
npm install
cp .env.example .env          # first time only
php artisan key:generate      # first time only
php artisan migrate --seed
composer run dev              # php artisan serve + queue:listen + pail + vite (starter kit script)
```

Separately when needed:

```bash
php artisan serve
npm run dev
php artisan queue:work
php artisan reverb:start --debug
php artisan schedule:work
```

Herd: site is already served at `http://<folder>.test` — only `npm run dev` + queue needed. Sail: `./vendor/bin/sail up -d`, prefix commands with `./vendor/bin/sail`.

SSR check: `npm run build:ssr && php artisan inertia:start-ssr`, then view-source: the HTML must contain rendered markup.

## 2. Verify in a browser

If you have a browser tool (built-in browser pane, Chrome extension, Playwright), open the page yourself and work through the list; otherwise give the user this checklist. Log in with the demo user from the seeder.

**Functional**
- [ ] Walk each acceptance criterion of the feature (happy path + one error path).
- [ ] Validation errors show next to the right fields; success flash appears.
- [ ] Permission-limited user: button hidden **and** direct URL returns 403/404.

**Console**
- [ ] No errors, React/Vue warnings (keys, hydration mismatch), or failed asset loads.
- [ ] No CSP violations (or only expected ones in report-only).

**Network**
- [ ] First load: HTML with `data-page` JSON (or SSR markup).
- [ ] Navigation: XHR with `X-Inertia: true`, JSON `{component, props, url, version}`.
- [ ] Partial reloads send `X-Inertia-Partial-Data` and return only those props; deferred props arrive in a follow-up request.
- [ ] PUT/PATCH/DELETE redirects with 303; validation failure redirects back with `props.errors` on the next page response.
- [ ] Props contain no secrets, other tenants' data, or unused heavy fields; payload size reasonable.
- [ ] Livewire: `/livewire/update` requests small; no unexpected full re-renders.

**DevTools**
- [ ] React/Vue DevTools: page component name matches Inertia component; props/state as expected; no re-render storms (Profiler) on typing/dragging.

**Layout & a11y**
- [ ] 375px, 768px, 1280px+: no horizontal scroll, nothing cut off.
- [ ] Dark mode.
- [ ] Keyboard only: tab order, visible focus, Escape closes dialogs, drag & drop keyboard alternative.
- [ ] Empty, loading (skeletons), and error states.
- [ ] Lighthouse accessibility ≥ 90 on key pages.

Record what was checked and the outcome in `docs/progress.md` (or the PR description).
