# File uploads (SEC-50 … SEC-52)

```php
use Illuminate\Validation\Rules\File;

'avatar' => ['required', File::image()->max(2 * 1024)->dimensions(Rule::dimensions()->maxWidth(4000)->maxHeight(4000))],
'attachment' => ['required', File::types(['pdf', 'png', 'jpg', 'jpeg', 'webp', 'docx', 'xlsx', 'txt'])->max(20 * 1024)],
```

- `File::types()` checks the detected MIME type and extension; don't rely on the client's `Content-Type`.
- Store on a private disk (`local` or `s3` with private visibility):

  ```php
  $path = $request->file('attachment')->store("tenants/{$tenantId}/attachments", 's3'); // random name
  ```

  or via `spatie/laravel-medialibrary` with `usingFileName(Str::uuid().'.'.$ext)`.
- Serve through a controller that authorizes, or with `Storage::temporaryUrl($path, now()->addMinutes(10))`.
- Force download for risky types: `Content-Disposition: attachment` and `X-Content-Type-Options: nosniff`.
- SVG: reject, or sanitise (`enshrined/svg-sanitize`) and serve with `Content-Type: image/svg+xml` from a different domain/CDN.
- Images displayed publicly: re-encode (Media Library conversions / Intervention Image) to strip metadata and payloads; strip EXIF GPS for privacy.
- Limits at every layer: PHP `upload_max_filesize`/`post_max_size`, Nginx `client_max_body_size`, validation `max`.
- Virus scanning for B2B document uploads: ClamAV container + queued scan before the file becomes available.
- Zip/CSV imports: limit rows/size, process in queued jobs, guard against zip bombs and CSV formula injection on export (prefix `=`, `+`, `-`, `@` cells with `'`).
