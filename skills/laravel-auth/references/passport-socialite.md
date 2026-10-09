# Passport (OAuth2 server) & Socialite (social login)

## Passport — only when you are an OAuth2 provider

Use when third parties need to request access to your users' data ("Connect with YourApp"), or machine clients need client-credentials. Otherwise Sanctum is simpler.

```bash
composer require laravel/passport
php artisan passport:install     # check current command set in the docs (keys, clients)
```

- Authorization code + **PKCE** for public clients; disable implicit and password grants (deprecated in OAuth 2.1).
- Scopes defined via `Passport::tokensCan([...])`; check with `scope:`/`scopes:` middleware.
- Keys in env/secrets (`PASSPORT_PRIVATE_KEY`, `PASSPORT_PUBLIC_KEY`), not in the repo.
- Token lifetimes: `Passport::tokensExpireIn()`, `refreshTokensExpireIn()`.

## Socialite — "Login with …"

```bash
composer require laravel/socialite
# extra providers: socialiteproviders/microsoft, /apple, etc.
```

```php
// config/services.php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

```php
Route::get('auth/{provider}/redirect', fn (string $provider) => Socialite::driver($provider)->redirect())
    ->whereIn('provider', ['google', 'microsoft', 'github'])->name('social.redirect');

Route::get('auth/{provider}/callback', SocialCallbackController::class)
    ->whereIn('provider', ['google', 'microsoft', 'github'])->name('social.callback');
```

```php
final class SocialCallbackController
{
    public function __invoke(string $provider, LinkSocialAccount $link): RedirectResponse
    {
        $social = Socialite::driver($provider)->user();
        $user = $link->handle($provider, $social);   // Action: find by provider id → else by VERIFIED email → else create

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
```

Rules (AUTH-06):
- `social_accounts` table: `user_id`, `provider`, `provider_id`, `unique(provider, provider_id)`; tokens encrypted if stored.
- Link to an existing user by email **only** if the provider says the email is verified (Google `email_verified`, Microsoft tenant policies vary — be careful).
- Users created via social login may have a null password; let them set one, and don't break "confirm password" flows for them (offer re-auth via provider).
- Allowlist providers in routes (`whereIn`), never pass arbitrary driver names.
