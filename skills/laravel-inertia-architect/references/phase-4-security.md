# Phase 4 — Validation & Security

The server is the only trust boundary. Client validation is for UX; every rule also exists server-side.

## Server-side: Form Requests

One FormRequest per write endpoint. `authorize()` calls the policy; `rules()` is strict and explicit.

```php
final class StoreCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Card::class, $this->route('list')]);
    }

    public function rules(): array
    {
        $workspace = app(CurrentWorkspace::class)->get();

        return [
            'title'       => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'],
            'due_at'      => ['nullable', 'date', 'after:now'],
            'label_ids'   => ['array', 'max:20'],
            'label_ids.*' => ['integer', Rule::exists('labels', 'id')->where('workspace_id', $workspace->id)],
            'assignee_ids.*' => [Rule::exists('workspace_user', 'user_id')->where('workspace_id', $workspace->id)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['title' => trim((string) $this->title)]);
    }
}
```

Rules worth remembering:

- **Scope `exists`/`unique` rules to the tenant** — otherwise users can attach other workspaces' labels by ID.
- Only pass `$request->validated()` (or a DTO built from it) to Actions; never `$request->all()`.
- Keep `$fillable` explicit on models; don't unguard globally.
- Use `Rule::enum()`, `File::types()->max()` for uploads, `Password::defaults()` configured in AppServiceProvider (`min(12)->uncompromised()` in production).

## Client-side: Inertia errors + React Hook Form

Default for simple forms — Inertia's `<Form>`/`useForm` already shows server errors:

```tsx
const form = useForm({ title: '' });
<form onSubmit={(e) => { e.preventDefault(); form.post(store(list).url, { preserveScroll: true }); }}>
  <Input value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
  <InputError message={form.errors.title} />
  <Button disabled={form.processing}>Add card</Button>
</form>
```

For complex forms (multi-step, dynamic arrays, instant feedback) use React Hook Form + Zod and bridge Inertia's server errors into RHF:

```tsx
const schema = z.object({
  title: z.string().trim().min(1, 'Title is required').max(500),
  due_at: z.string().datetime().nullable(),
});
type Values = z.infer<typeof schema>;

export function CardForm({ list }: { list: ListData }) {
  const { register, handleSubmit, setError, formState } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { title: '', due_at: null },
  });

  const onSubmit = (values: Values) =>
    router.post(store(list).url, values, {
      preserveScroll: true,
      onError: (errors) =>
        Object.entries(errors).forEach(([field, message]) =>
          setError(field as keyof Values, { type: 'server', message })),
    });

  return (
    <form onSubmit={handleSubmit(onSubmit)} noValidate>
      <Input {...register('title')} aria-invalid={!!formState.errors.title} />
      <InputError message={formState.errors.title?.message} />
    </form>
  );
}
```

Optional: **Laravel Precognition** (`laravel-precognition-react`) for live validation using the real FormRequest rules — no duplicated Zod schema. Good for registration/checkout forms.

Keep Zod schemas and FormRequest rules in sync; when they drift, the server wins. For larger apps, generate one from the other (laravel-data validation attributes → TS) instead of maintaining both by hand.

## Authorization

- **Policies** for every model (`php artisan make:policy CardPolicy --model=Card`). Policy methods check workspace membership + Spatie permission:

  ```php
  public function update(User $user, Card $card): bool
  {
      return $user->belongsToWorkspace($card->workspace_id)
          && $user->hasPermissionTo(Permission::CardsUpdate);
  }
  ```

- **Gates** for non-model abilities (`viewAdmin`, `accessBilling`).
- `Gate::before()` for super-admins only if they truly bypass everything — prefer explicit checks.
- Test every policy method in Pest (allowed + denied + other-workspace).
- Filament resources reuse the same policies automatically.

## XSS

- React escapes output by default. The risk is `dangerouslySetInnerHTML` — use it only for rich text that was **sanitised server-side** (e.g. `mews/purifier` / HTML Purifier, or `symfony/html-sanitizer`) on save, with a strict allowlist.
- Never put user input into `href` without validating the scheme (`javascript:` URLs) — validate `url` with `active_url`/scheme allowlist.
- Blade: always `{{ }}`, never `{!! !!}` with user data. Inertia's root view passes page JSON safely via `@inertia`.

## CSRF

- Laravel's CSRF middleware covers all `web` routes. Inertia's client sends the `XSRF-TOKEN` cookie as `X-XSRF-TOKEN` automatically.
- Keep `SESSION_SAME_SITE=lax` (or `strict`), `SESSION_SECURE_COOKIE=true` in production, `SESSION_HTTP_ONLY=true`.
- Handle `419` gracefully (see Phase 1).
- Webhooks (Stripe/Mollie) go in a route excluded from CSRF **and** verify signatures.

## SQL injection

- Eloquent/query builder bind parameters — safe by default.
- Danger zones: `DB::raw`, `whereRaw`, `orderByRaw`, `selectRaw` with user input, and dynamic column names. Use bindings (`whereRaw('x = ?', [$v])`) and allowlist sortable columns:

  ```php
  $sort = in_array($request->sort, ['title', 'due_at', 'created_at'], true) ? $request->sort : 'created_at';
  ```

## Content Security Policy

Use `spatie/laravel-csp` with a nonce for Vite assets (`assets/app/Support/Csp/AppPolicy.php`):

```php
// AppServiceProvider::boot
Vite::useCspNonce();
```

Start in report-only mode (`CSP_REPORT_ONLY=true`), check the browser console for violations, then enforce. Allow `ws(s)://` for Reverb in `connect-src`, the S3/MinIO host in `img-src`, and Vite's dev server (`localhost:5173`) only when `app()->isLocal()`.

Also add security headers (in middleware or at the proxy): `Strict-Transport-Security`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`, `X-Frame-Options: DENY` (or CSP `frame-ancestors 'none'`).

## Rate limiting

```php
// AppServiceProvider::boot
RateLimiter::for('api', fn (Request $r) => Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()));
RateLimiter::for('login', fn (Request $r) => [
    Limit::perMinute(5)->by(Str::lower($r->input('email')).'|'.$r->ip()),
    Limit::perMinute(20)->by($r->ip()),
]);
RateLimiter::for('invites', fn (Request $r) => Limit::perHour(30)->by($r->user()->id));
RateLimiter::for('uploads', fn (Request $r) => Limit::perMinute(20)->by($r->user()->id));
```

Apply with `->middleware('throttle:invites')`. Fortify's login throttle is configured in `config/fortify.php`.

## Other essentials

- **Auth hardening:** email verification, 2FA (Fortify), passkeys (Laravel 13), session invalidation on password change, "log out other devices".
- **Mass assignment & IDOR:** route model binding + `scopeBindings()` + policies; never trust IDs in payloads without tenant scoping.
- **File uploads:** validate mime by content (`File::types()`), max size, store on a private disk, serve via signed temporary URLs; never execute uploaded files; strip EXIF from images if privacy matters.
- **Secrets:** only in `.env`/Docker secrets; `APP_DEBUG=false` in production; rotate `APP_KEY` only with `APP_PREVIOUS_KEYS`.
- **Dependencies:** `composer audit` and `npm audit` in CI.
- **Logging:** never log passwords, tokens or full request bodies; Laravel's `Log` context + `#[\SensitiveParameter]` on sensitive arguments.
