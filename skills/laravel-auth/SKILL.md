---
name: laravel-auth
description: Choose, install and harden authentication in Laravel — official starter kits (React, Vue, Svelte, Livewire, WorkOS AuthKit variant), Laravel Fortify (headless auth, 2FA, passkeys), legacy Laravel Breeze and Jetstream projects, Sanctum (SPA cookies + API tokens), Passport (OAuth2 server), Socialite (Google/Microsoft/GitHub login), email verification, invitations and team/workspace membership. Use this whenever the user sets up login/registration, asks "Breeze or Jetstream or Fortify?", adds 2FA, passkeys, social login, SSO, API tokens or OAuth, maintains an existing Breeze/Jetstream app, or builds invite/team flows.
---

# Laravel Auth

Authentication is the front door; use battle-tested first-party pieces and configure them strictly. Never hand-roll password storage, reset tokens, 2FA or session handling.

## Step 1 — Pick the right tool

| Need | Use | Notes |
|---|---|---|
| New app with UI (React/Vue/Svelte via Inertia, or Livewire) | **Official starter kit** (`laravel new app` → pick stack) | Code lives in your app; built on **Fortify**; includes login, register, reset, verification, 2FA, settings; passkeys added in 2026 (verify version) |
| Enterprise SSO, social, passkeys, magic codes without building them | **Starter kit — WorkOS AuthKit variant** | external IdP (WorkOS); check pricing/data-residency for EU clients |
| Custom frontend, headless auth endpoints | **Fortify** alone | you build the views; Fortify provides routes/actions |
| Existing app on **Breeze** or **Jetstream** | keep it, maintain it | both are no longer actively developed since the Laravel 12 starter kits; don't start new projects on them — see `references/legacy-breeze-jetstream.md` |
| SPA on same top-level domain or mobile app tokens | **Sanctum** | cookie auth for SPAs, personal access tokens for mobile/3rd party |
| Your app is an OAuth2 provider for third parties | **Passport** | only if you truly need OAuth2 flows (authorization code + PKCE, client credentials) |
| "Login with Google/Microsoft/GitHub/Apple" | **Socialite** (+ community providers) | link to existing accounts by verified email only |
| Admin panel login | **Filament** panel login | separate from customer auth or shared guard — see `laravel-filament` |

When the choice isn't obvious, ask the user with these options and a recommendation.

## Rules

- **AUTH-01** Use a first-party kit or package; never custom password hashing, reset tokens or 2FA.
- **AUTH-02** Email verification (`MustVerifyEmail` + `verified` middleware) for any app where email matters (invites, billing, notifications).
- **AUTH-03** 2FA available for all users; enforced for admins/owners (SEC-42). Passkeys offered when the installed Fortify/kit supports them.
- **AUTH-04** All auth endpoints rate limited (SEC-41); generic error messages (no account enumeration).
- **AUTH-05** Sensitive actions require `password.confirm` (or recent-2FA): email change, password change, delete account, API tokens, billing, role changes.
- **AUTH-06** Social login links accounts only on a **verified** provider email; new social users still get a local user record; store provider ids in a separate `social_accounts` table, not on `users`.
- **AUTH-07** API tokens (Sanctum) have abilities/scopes, expiry (`expiration` in `config/sanctum.php`), are hashed (default), shown once, revocable, and listed in the UI.
- **AUTH-08** Invitations use signed, expiring, single-use tokens bound to the invited email; accepting checks the logged-in user's email matches.
- **AUTH-09** Logout invalidates the session and regenerates the CSRF token (framework default — don't override); "log out other devices" available.
- **AUTH-10** Guards are explicit: `web` for sessions, `sanctum` for API; never mix admin and customer users in one table without a clear `is_super_admin` flag + panel gate.

## References

- `references/starter-kits.md` — install per stack, what's included, customising safely.
- `references/fortify.md` — features config, actions, views, 2FA, passkeys.
- `references/legacy-breeze-jetstream.md` — maintaining and upgrading Breeze/Jetstream apps; migrating to the new kits.
- `references/sanctum.md` — SPA cookie auth, token auth, abilities, mobile.
- `references/passport-socialite.md` — OAuth2 server and social login.
- `references/invitations-teams.md` — invite flow, workspace membership, switching teams.
