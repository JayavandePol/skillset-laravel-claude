---
name: laravel-standards
description: The baseline clean-code and architecture rules for ANY Laravel project (Laravel 11–13, PHP 8.3+) — strict types, Actions/Services/DTOs/enums, thin controllers, config & env handling, naming, error handling, events/jobs, and enforced tooling (Pint, Larastan, Rector, Pest arch tests). Use this whenever you write, refactor, generate or review PHP code in a Laravel app — controllers, models, actions, jobs, commands, service providers, config — even for a small change, and whenever the user asks for "clean code", "best practices", "conventions", "structure" or "how should I organise this" in Laravel. Other laravel-* skills assume these rules.
---

# Laravel Standards

These are the house rules for every Laravel project. They exist so that code written in any session, by any agent or developer, looks like it was written by one careful senior engineer: predictable to navigate, safe to change, and checked by tools rather than by memory.

**Before writing code in a project:**

1. Read `composer.json` (Laravel/PHP versions, installed packages), `bootstrap/app.php`, and the project's own `CLAUDE.md`/`AGENTS.md`/Boost guidelines if present. Project conventions that are deliberate override these defaults — but flag rules that look like accidents (e.g. no strict types anywhere).
2. Match the surrounding code's style where these rules are silent.
3. If the tooling below isn't installed, propose adding it (assets in `assets/`) — enforcement by tools beats enforcement by review.

## The rules

Each rule has an ID so reviews and audits can cite it. Details and examples are in the reference files.

### Structure — `references/architecture.md`

- **STD-01** Business logic lives in **Actions** (`app/Actions/<Domain>/VerbNoun.php`, `final`, one public `handle()`), never in controllers, Livewire components, Filament resources, jobs or models.
- **STD-02** Controllers are thin: authorize → validate (FormRequest) → call Action/query → return response. No queries beyond route-model binding and simple reads; no `DB::` in controllers.
- **STD-03** **Services** wrap external systems or cross-cutting infrastructure (payments, PDF, AI, SMS). Bind behind an interface when it needs faking in tests.
- **STD-04** Complex/reused reads go in **query objects** (`app/Queries`) or model scopes, not repositories — add a repository interface only when there's a second real implementation.
- **STD-05** Data crossing layers uses typed **DTOs** (readonly classes or `spatie/laravel-data`), not loose arrays. `$request->validated()` → DTO → Action.
- **STD-06** Fixed sets of values are **backed enums** (`app/Enums`) with casts on models — no magic strings/ints for statuses, roles, types.
- **STD-07** Side effects that aren't part of the core write (emails, webhooks, analytics, cache busting) go through **events/listeners or queued jobs**, dispatched after commit.

### PHP code — `references/php-style.md`

- **STD-10** `declare(strict_types=1);` in every PHP file under `app/`, `database/`, `routes/`, `tests/`, `config/`.
- **STD-11** Full type declarations on all parameters, return types and properties; generics in PHPDoc for collections (`@return Collection<int, Card>`).
- **STD-12** Classes are `final` by default (except models, which factories/Filament may extend, and classes designed for extension). Prefer `readonly` properties and constructor promotion.
- **STD-13** No `else` after `return`; early returns; methods under ~25 lines; no nesting deeper than 2.
- **STD-14** No dead code, commented-out code, `dd()`, `dump()`, `ray()`, `var_dump()`, `die`, or `exit` in committed code.
- **STD-15** No global helpers for things the container can inject; no facades inside domain classes when constructor injection is natural (facades in controllers/routes/tests are fine).
- **STD-16** Use Laravel's helpers and collections over hand-rolled PHP where they are clearer (`Str::`, `Arr::`, `collect()`, `Carbon`/`now()`), and `CarbonImmutable` for dates.

### Configuration — `references/config-and-env.md`

- **STD-20** `env()` is called **only** inside `config/*.php`. Everything else uses `config()`. (Otherwise config caching breaks.)
- **STD-21** Every new env var is added to `.env.example` with a safe placeholder and documented; no secrets in `.env.example`, code, tests or git history.
- **STD-22** Hard-coded URLs, emails, limits and feature switches go in config (or Pennant flags), not code.
- **STD-23** `config:cache`, `route:cache`, `event:cache`, `view:cache` must succeed — no closures in routes that block route caching for production routes.

### Naming — `references/naming.md`

- **STD-30** Follow Laravel naming: singular `StudlyCase` models, plural `snake_case` tables, `snake_case` columns, `camelCase` methods/variables, `kebab-case` URLs, dotted `snake` route names (`boards.cards.store`), invokable controllers named for the action (`ArchiveBoardController`).
- **STD-31** Booleans read as questions (`is_active`, `hasAccess()`), timestamps end in `_at`, foreign keys `<model>_id`.
- **STD-32** Names describe intent, not type (`$overdueCards`, not `$array2`). No abbreviations except well-known ones (`id`, `url`, `db`).

### Errors & logging — `references/errors-and-logging.md`

- **STD-40** Throw domain-specific exceptions (`CardLimitReached`) from Actions; render/report them centrally in `bootstrap/app.php` `withExceptions()`.
- **STD-41** Never swallow exceptions (`catch (Throwable) {}`); either handle meaningfully, rethrow, or `report()`.
- **STD-42** Logs use context arrays (`Log::info('Invite sent', ['invite_id' => $id])`), never sensitive data (passwords, tokens, full card numbers, BSN). Mark sensitive params `#[\SensitiveParameter]`.
- **STD-43** User-facing errors are translated and friendly; stack traces only with `APP_DEBUG=true` locally.

### Transactions & consistency

- **STD-50** Multi-write operations run in `DB::transaction()`; jobs/events/mail triggered inside use `afterCommit`.
- **STD-51** Idempotency for anything retried (jobs, webhooks, payment callbacks): unique keys or `ShouldBeUnique`.

### Tooling — enforced (`assets/`)

- **STD-60** **Pint** (`assets/pint.json`) — code style; runs on save via the plugin hook and in CI (`pint --test`).
- **STD-61** **Larastan** (`assets/phpstan.neon`) — level 8 for new projects, baseline for legacy (`--generate-baseline`), never lower the level to make CI pass.
- **STD-62** **Rector** (`assets/rector.php`) — automated upgrades and dead-code removal; run before upgrades.
- **STD-63** **Pest arch tests** (`assets/tests/ArchTest.php`) — encode STD rules as tests so they can't regress.
- **STD-64** Frontend: TypeScript `strict`, ESLint + Prettier, `npm run types` passes.
- **STD-65** `composer.json` has scripts: `lint`, `analyse`, `test`, `check` (all of them). CI runs `composer check`.
- **STD-66** A project `CLAUDE.md` (template `assets/CLAUDE.project.md`) records commands, conventions and don'ts for future sessions.

Asset files: `pint.json`, `phpstan.neon`, `rector.php`, `tests/ArchTest.php`, `composer-scripts.json`, `.editorconfig`, `CLAUDE.project.md`, and `app/Actions/MoveCard.php` (example Action with fractional ordering). Copy them into the project and adapt.

## Definition of done (every change)

- [ ] Rules above respected (or a written reason in the PR/commit body).
- [ ] Tests written or updated (see `laravel-testing`), all green.
- [ ] `composer check` passes (Pint, Larastan, Pest incl. arch).
- [ ] Security rules from `laravel-security` considered for any input, auth, upload or query change.
- [ ] Migrations reversible or explicitly marked forward-only (see `laravel-database`).
- [ ] No new N+1 queries on touched pages.
- [ ] Docs/`.env.example`/`CLAUDE.md` updated if behaviour or setup changed.
