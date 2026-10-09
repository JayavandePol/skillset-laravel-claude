# Laravel Fortify

Headless authentication backend: routes, controllers and actions without views. Powers the starter kits.

## Install (standalone)

```bash
composer require laravel/fortify
php artisan fortify:install     # publishes config/fortify.php, FortifyServiceProvider, actions, migrations
php artisan migrate
```

## Configure features

```php
// config/fortify.php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::updateProfileInformation(),
    Features::updatePasswords(),
    Features::twoFactorAuthentication([
        'confirm' => true,            // require a valid code before enabling
        'confirmPassword' => true,
    ]),
    // Passkeys: available in recent Fortify versions — check the 13.x Fortify docs for the feature name/options.
],
'limiters' => [
    'login' => 'login',
    'two-factor' => 'two-factor',
],
'home' => '/dashboard',
```

## Views (headless setup)

```php
// FortifyServiceProvider::boot
Fortify::loginView(fn () => inertia('auth/login'));            // or view('auth.login')
Fortify::registerView(fn () => inertia('auth/register'));
Fortify::requestPasswordResetLinkView(fn () => inertia('auth/forgot-password'));
Fortify::resetPasswordView(fn (Request $request) => inertia('auth/reset-password', ['token' => $request->route('token'), 'email' => $request->email]));
Fortify::verifyEmailView(fn () => inertia('auth/verify-email'));
Fortify::twoFactorChallengeView(fn () => inertia('auth/two-factor-challenge'));
Fortify::confirmPasswordView(fn () => inertia('auth/confirm-password'));

RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(Str::lower($request->string(Fortify::username())).'|'.$request->ip()));
RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));
```

## Custom login logic

```php
Fortify::authenticateUsing(function (Request $request): ?User {
    $user = User::where('email', Str::lower($request->email))->first();

    if ($user && $user->is_active && Hash::check($request->password, $user->password)) {
        return $user;
    }

    return null; // generic failure message
});
```

## Enforcing 2FA for admins

```php
final class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->requiresTwoFactor() && $user->two_factor_confirmed_at === null) {
            return redirect()->route('two-factor.setup')->with('warning', __('Enable two-factor authentication to continue.'));
        }

        return $next($request);
    }
}
```

## Tests

The starter kits ship Pest tests for every Fortify flow. Standalone: write tests for login (success, wrong password, throttled), registration, reset, verification, 2FA challenge.
