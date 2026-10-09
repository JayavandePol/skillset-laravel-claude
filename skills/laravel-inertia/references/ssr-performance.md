# SSR & frontend performance

## When to use SSR

- Public, SEO-relevant pages (marketing, public boards/profiles, docs): yes.
- Logged-in app only: optional — saves a container if disabled; decide in the blueprint.

## Setup (v3)

1. `resources/js/ssr.tsx` (starter kits include it); the `@inertiajs/vite` plugin wires SSR and runs it automatically in dev.
2. Build: `npm run build:ssr` (check `package.json`).
3. Production process: `php artisan inertia:start-ssr` (default port 13714) in its own container; `php artisan inertia:check-ssr` for health; `php artisan inertia:stop-ssr` on deploy.
4. `config/inertia.php` → `ssr.enabled`, `ssr.url` (e.g. `http://ssr:13714` in Docker).

## SSR-safe code (FE-09)

```tsx
const [width, setWidth] = useState<number | null>(null);
useEffect(() => setWidth(window.innerWidth), []);
```

- No browser globals at module scope or during render.
- Client-only widgets (charts, editors, maps, dnd) render a placeholder on the server and mount on the client (`React.lazy` + `Suspense`, `<ClientOnly>` wrapper, Vue `<ClientOnly>`/`onMounted`).
- Dates: render with a fixed timezone/locale or on the client to avoid hydration mismatches.
- `<Head>` for title/meta per page; root Blade keeps `@inertiaHead`.

## Performance checklist

- [ ] Pages lazy-loaded (default with `@inertiajs/vite` page resolution) → one chunk per page.
- [ ] Heavy libraries (editor, charts, PDF viewer) dynamically imported.
- [ ] Props trimmed (FE-04); deferred props for expensive data; check payload size in the network tab (< ~100 KB typical).
- [ ] Prefetch main navigation links.
- [ ] Images: responsive sizes from Media Library conversions, `loading="lazy"`, width/height set.
- [ ] Fonts self-hosted or preloaded; `font-display: swap`.
- [ ] `npx vite-bundle-visualizer` before launch; budgets for the main chunk.
- [ ] React Compiler enabled where the kit supports it; avoid unnecessary context re-renders.
- [ ] Lighthouse / Core Web Vitals on key public pages (LCP < 2.5s, INP < 200ms, CLS < 0.1).
