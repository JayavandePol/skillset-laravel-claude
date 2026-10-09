# Injection & XSS (SEC-20 … SEC-23)

## SQL

Safe by default:

```php
Invoice::where('number', $request->validated('number'))->first();
DB::select('select * from invoices where tenant_id = ? and number = ?', [$tenantId, $number]);
```

Danger zones and the fix:

```php
// ❌ orderBy with user input
$query->orderBy($request->sort);
// ✅ allowlist
$sort = Arr::get(['date' => 'issued_at', 'number' => 'number', 'total' => 'total_cents'], $request->string('sort'), 'issued_at');
$direction = $request->string('dir')->lower()->value() === 'asc' ? 'asc' : 'desc';
$query->orderBy($sort, $direction);

// ❌ interpolation in raw
$query->whereRaw("LOWER(name) LIKE '%{$term}%'");
// ✅ bindings
$query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($term).'%']);
```

- `spatie/laravel-query-builder` gives allowlisted filters/sorts/includes from query strings.
- JSON column paths (`where('meta->'.$key, …)`) — allowlist `$key`.
- LIKE wildcards: escape `%` and `_` in user terms if exact substring matters.

## XSS

- Blade `{{ }}` escapes; React/Vue/Svelte escape text by default.
- Raw HTML (`{!! !!}`, `dangerouslySetInnerHTML`, `v-html`, `{@html}`): only for content sanitised on save:

  ```php
  // composer require symfony/html-sanitizer
  $sanitizer = new HtmlSanitizer((new HtmlSanitizerConfig())->allowSafeElements()->allowLinkSchemes(['https', 'mailto']));
  $card->description_html = $sanitizer->sanitize($validated['description']);
  ```

- Markdown: render with a CommonMark config `html_input => 'strip'`, `allow_unsafe_links => false` (Laravel's `Str::markdown($text, ['html_input' => 'strip', 'allow_unsafe_links' => false])`).
- Attributes: never build `href`, `src`, `style` or event attributes from unvalidated input. `javascript:` URLs are the classic hole.
- JSON in Blade: `@js($data)` / `Js::from()` — not `json_encode` inside `<script>`.
- Inertia props: they're JSON-encoded safely into `data-page`; still don't send HTML you'll render raw unless sanitised.

## Open redirect & SSRF (SEC-22)

```php
// Redirect only to internal paths
$target = $request->string('redirect')->value();
return str_starts_with($target, '/') && ! str_starts_with($target, '//')
    ? redirect($target)
    : redirect()->route('dashboard');
```

Server-side fetch of user URLs (link previews, webhooks to customer endpoints): resolve the host, reject private/loopback/link-local ranges (10/8, 172.16/12, 192.168/16, 127/8, 169.254/16, ::1, fc00::/7), set timeouts, disable redirects or re-validate each hop.

## Command & code execution (SEC-23)

```php
// ✅ Process with an argument array — no shell interpolation
Process::run(['pdftotext', $path, '-'])->throw();
```

No `unserialize()` on anything a user can influence (cookies, request, cache keys they control) — use JSON.
