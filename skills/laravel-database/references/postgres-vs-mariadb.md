# PostgreSQL vs MariaDB/MySQL vs SQLite

| Topic | PostgreSQL 16/17 | MariaDB 11 LTS / MySQL 8.4 | SQLite |
|---|---|---|---|
| JSON | `jsonb` + GIN indexes, rich operators | `json` (MariaDB: LONGTEXT alias + check); index via generated columns | JSON1 functions |
| Partial / expression indexes | yes | expression indexes (MySQL 8), no partial | partial yes |
| Transactional DDL | yes — failed migrations roll back | no | yes |
| Case-insensitive | `citext`, `ILIKE`, collations | default `*_ci` collations | `COLLATE NOCASE` |
| Full-text | `tsvector` + GIN | `FULLTEXT` | FTS5 |
| Vectors / AI | `pgvector` (Laravel 13 native vector queries) | MariaDB 11.7+ `VECTOR` (check Laravel support) | extensions only |
| Concurrent index build | `CREATE INDEX CONCURRENTLY` | online DDL (`ALGORITHM=INPLACE`) | n/a |
| Upsert | `ON CONFLICT` | `ON DUPLICATE KEY` | `ON CONFLICT` |
| Hosting | everywhere, Dokploy one-click | everywhere, typical shared hosting | file, no server |

## Recommendation

- **PostgreSQL** by default for new SaaS.
- **MariaDB/MySQL** when the host or existing infra dictates; avoid PG-only features then.
- **SQLite** for local dev/tests of simple apps and some single-server production apps; run CI against the production engine if it differs.

## Engine-specific gotchas

- PG: column names are case-sensitive when quoted; `ILIKE` isn't portable; sequences after manual id inserts need resetting.
- MariaDB: `json` columns aren't real JSON type in MariaDB — use generated columns for indexing; strict mode must stay on (`'strict' => true` in config).
- MySQL/MariaDB: `utf8mb4` + `utf8mb4_unicode_ci` (or `_0900_ai_ci` on MySQL 8) for emoji support.
- All: set `DB_TIMEZONE`/app timezone to UTC.
