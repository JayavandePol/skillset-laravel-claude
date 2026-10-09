---
name: laravel-database
description: Rules and patterns for Laravel databases and Eloquent — schema/ERD design, migrations with strict foreign keys and indexes, safe zero-downtime changes, Eloquent models, casts, scopes, relationships, multi-tenancy (shared DB with tenant_id or database-per-tenant), N+1 prevention, pagination, factories and seeders, PostgreSQL vs MariaDB/MySQL specifics. Use this whenever you create or change migrations, models, relationships, queries, seeders or factories, design a schema/ERD, debug slow queries or N+1 problems, or choose between PostgreSQL, MariaDB, MySQL or SQLite in a Laravel project.
---

# Laravel Database

Schema mistakes are the most expensive to fix later — they outlive every controller and component. Design deliberately, constrain strictly, and make the database enforce what it can.

## Rules

### Schema & migrations — `references/migrations.md`
- **DB-01** Every foreign key uses `foreignId()->constrained()` (or `foreignUlid`) with an explicit `cascadeOnDelete()` / `nullOnDelete()` / `restrictOnDelete()` decision.
- **DB-02** Indexes match real queries: composite indexes in filter → sort order; every FK indexed; unique constraints for business uniqueness (not just validation).
- **DB-03** Column types are exact: lengths that match validation, `unsignedBigInteger` money in minor units, `decimal` for quantities, `timestamp`/`timestampTz` for instants, `date` for dates, `json`/`jsonb` only for genuinely schemaless data.
- **DB-04** Migrations are immutable once deployed; changes are new migrations. `down()` works or the migration is documented as forward-only.
- **DB-05** Production-safe changes: add nullable → backfill (queued, chunked) → constrain; never rename/drop columns still read by the running release (expand/contract).
- **DB-06** Public identifiers are ULIDs/UUIDs when IDs appear in URLs or APIs; internal joins can stay bigint.
- **DB-07** Every tenant-owned table carries the tenant key and an index starting with it.

### Eloquent — `references/eloquent.md`
- **DB-10** `Model::shouldBeStrict()` outside production; lazy-loading violations logged in production.
- **DB-11** `$fillable` explicit; casts declared in `casts()` (enums, immutable dates, `encrypted`, `AsCollection`, money value objects).
- **DB-12** Relationships fully typed (`: BelongsTo`) with generics in PHPDoc; inverse relations defined.
- **DB-13** Reusable conditions are scopes (`#[Scope]` attribute or `scopeX`), complex reads are query objects.
- **DB-14** No business workflows in observers/model events; observers only for model-level concerns.

### Performance — `references/n-plus-one.md`
- **DB-20** Pages and API endpoints eager load what they render (`with`, `load`, `withCount`, `withExists`, `withSum`) and select only needed columns for large tables.
- **DB-21** Never unbounded reads: paginate (`paginate`, `simplePaginate`, `cursorPaginate`), `chunkById`, `lazyById`.
- **DB-22** Hot pages have a query-count test; slow queries investigated with `EXPLAIN`.
- **DB-23** Write-heavy batch work uses `upsert`, `insert` in chunks, or queued jobs — not per-row `save()` in loops.

### Tenancy — `references/multi-tenancy.md`
- **DB-30** Pick the tenancy model in the blueprint (shared DB + tenant column by default; DB-per-tenant via `stancl/tenancy` only for strong isolation needs) and apply it consistently.
- **DB-31** Tenant scoping is enforced in three places: global scope, scoped route bindings / policies, and tenant-scoped validation rules.

### Data & seeding — `references/factories-seeders.md`
- **DB-40** Every model has a factory with meaningful states; tests never depend on seeders.
- **DB-41** Seeders are idempotent; demo data only in local/staging; production seeders limited to reference data (roles, permissions, plans).

## Choosing the database

Read `references/postgres-vs-mariadb.md`. Default: **PostgreSQL** (jsonb, partial indexes, transactional DDL, pgvector for AI search). Use **MariaDB/MySQL** when hosting or the team requires it. **SQLite** is fine for local dev/tests of simple apps, but run CI against the production engine.

## ERD output format

Use Mermaid `erDiagram` with table notes listing indexes, uniques and delete strategies — see `references/migrations.md` for a full example.
