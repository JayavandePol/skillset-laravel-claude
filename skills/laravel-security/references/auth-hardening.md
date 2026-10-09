# Authentication hardening (SEC-40 … SEC-44)

## Password policy

```php
// AppServiceProvider::boot
Password::defaults(fn () => $this->app->isProduction()
    ? Password::min(12)->mixedCase()->numbers()->uncompromised()
    : Password::min(8));
```

## Rate limits

```php
RateLimiter::for('login', fn (Request $request) => [
    Limit::perMinute(5)->by(Str::lower($request->string('email')).'|'.$request->ip()),
    Limit::perMinute(30)->by($request->ip()),
]);
```

Fortify: `'limiters' => ['login' => 'login', 'two-factor' => 'two-factor']` in `config/fortify.php`. Also limit: register, forgot-password, resend-verification, invites, magic links.

## 2FA, passkeys, sessions

- Fortify two-factor (TOTP + recovery codes) — included in the starter kits. Require it for admins (middleware that redirects to 2FA setup when `two_factor_confirmed_at` is null).
- Passkeys: first-party support landed in Fortify/starter kits in 2026 — check the Fortify docs for the installed version; alternatively `spatie/laravel-passkeys` or WorkOS AuthKit.
- `password.confirm` middleware for sensitive actions (change email, delete account, billing, API tokens).
- Offer "log out other browser sessions" (`Auth::logoutOtherDevices()` with the database session driver).
- Invalidate remember tokens and sessions on password change.

## Account enumeration

- Login error: generic "These credentials do not match our records."
- Password reset: same response whether or not the email exists.
- Registration: unavoidable to some degree; rate limit and add bot protection.

## Admin & tooling access

```php
// Horizon / Pulse / Telescope gates
Gate::define('viewHorizon', fn (User $user) => $user->is_super_admin);
Gate::define('viewPulse', fn (User $user) => $user->is_super_admin);
```

Filament: `canAccessPanel()` checks super-admin flag + verified email (+ 2FA). Optionally IP-allowlist `/admin` at the proxy.

## Signed URLs

```php
$url = URL::temporarySignedRoute('invoices.download', now()->addMinutes(30), ['invoice' => $invoice]);
Route::get('invoices/{invoice}/download', DownloadInvoiceController::class)->name('invoices.download')->middleware('signed');
```

Still authorize inside the controller if the link could be forwarded and that matters.

## Impersonation

Only super-admins, logged in the audit log, visible banner while impersonating, can't impersonate other admins.
