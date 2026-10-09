# Official starter kits

Since Laravel 12 the starter kits are **application templates**, not packages: the auth code is copied into your app and you own it. Check https://laravel.com/docs/starter-kits for the current list and options.

## Install

```bash
composer global require laravel/installer
laravel new my-app            # interactive: choose React / Vue / Svelte / Livewire, auth provider (Laravel or WorkOS), testing (Pest)
# Non-interactive examples (check `laravel new --help` for current flags):
laravel new my-app --react --pest
laravel new my-app --livewire --pest
```

Or from the template repo: `composer create-project laravel/react-starter-kit my-app` (also `vue-starter-kit`, `svelte-starter-kit`, `livewire-starter-kit`).

Then:

```bash
cd my-app
npm install && npm run build
php artisan migrate
composer run dev
```

## What's included (typical)

- Fortify backend: login, registration, password reset, email verification, password confirmation, 2FA (TOTP + recovery codes), passkeys in recent versions.
- Settings pages: profile, password, appearance (dark mode), two-factor, (passkeys).
- Inertia kits: TypeScript, Tailwind v4, shadcn/ui (React), shadcn-vue (Vue), shadcn-svelte, Wayfinder typed routes.
- Livewire kit: Livewire 4, Flux UI (free components), Tailwind v4.
- Pest tests for all auth flows — keep them green when customising.

## Customising safely

- Change views/components freely; keep Fortify's actions (`app/Actions/Fortify/*`) as the place for registration/password logic.
- Add fields to registration in `CreateNewUser` with validation; keep `Password::defaults()`.
- Turn features on/off in `config/fortify.php` → `features`.
- Removing registration (invite-only apps): disable `Features::registration()` and remove the routes/links.
- Rebrand: logos and app name in layout components; `APP_NAME` in `.env`.

## WorkOS AuthKit variant

- Pick "WorkOS" as auth provider in `laravel new`. Adds social login, passkeys, magic auth codes, SSO via WorkOS-hosted AuthKit.
- Needs `WORKOS_CLIENT_ID`, `WORKOS_API_KEY`, `WORKOS_REDIRECT_URL` in `.env`.
- Consider for B2B apps that need SAML/OIDC SSO. For EU clients, review data processing (US provider) in the DPA.

## Adding a starter kit later

Starter kits are meant for new apps. For an existing app without auth, either scaffold a fresh kit app and port your domain code into it, or install Fortify (see fortify.md) and build the views.
