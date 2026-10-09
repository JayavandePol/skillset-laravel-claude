# Privacy — GDPR / AVG (SEC-90 … SEC-92)

## Data minimisation & encryption

```php
protected function casts(): array
{
    return [
        'iban' => 'encrypted',
        'bsn' => 'encrypted',
        'medical_notes' => 'encrypted',
        'api_settings' => 'encrypted:array',
    ];
}
```

- Encrypted columns can't be searched — store a keyed hash (`hash_hmac('sha256', $value, config('app.key'))`) alongside when lookups are needed.
- Don't collect fields "just in case"; every personal field has a purpose in the processing register.

## Data subject rights

- **Export** (art. 15/20): queued job builds a ZIP/JSON of the user's data, emailed as a temporary signed link (`spatie/laravel-personal-data-export` helps).
- **Delete** (art. 17): `DeleteAccount` Action — soft-delete with a grace period (e.g. 30 days), then a scheduled job hard-deletes or anonymises; remove media; revoke tokens; notify processors where needed.
- **Rectification**: profile editing.
- Retention: `Prunable`/`MassPrunable` models with `prunable()` queries + `model:prune` scheduled daily (old logs, expired invites, soft-deleted accounts).

## Logging & tracking

- Activity logs record *who/what/when*, not full personal payloads; exclude sensitive attributes (`logExcept`).
- Sentry/Flare: enable PII scrubbing, don't send request bodies or cookies.
- Analytics: prefer privacy-friendly (Plausible, Fathom, self-hosted Umami) — no cookie banner needed when no tracking cookies are set. If you set non-essential cookies, consent first.

## Hosting & processors

- EU hosting and EU S3 region; list processors (hosting, mail, error tracking, payment) for the DPA.
- Backups encrypted and covered by the same retention policy.

## Security of processing (art. 32)

The `laravel-security` rules are the technical measures; keep a short `docs/security.md` listing them for audits.
