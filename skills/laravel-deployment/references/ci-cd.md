# CI/CD

Template: `assets/.github/workflows/ci.yml` (tests) — copy to the project's `.github/workflows/`.

## Pipeline stages (DEP-10)

1. **Quality** — `pint --test`, `phpstan analyse`, `rector --dry-run` (optional), `npm run lint`, `npm run types`.
2. **Tests** — Pest in parallel against the production DB engine (service container), then browser tests (Playwright).
3. **Security** — `composer audit`, `npm audit --omit=dev --audit-level=high`; optionally Trivy scan of the image.
4. **Build** — Docker image tagged with the commit SHA, pushed to GHCR.
5. **Deploy** — on `main` only, after all jobs pass: trigger Dokploy/Forge/Cloud deploy (webhook/API) with the SHA tag. Production environment with required reviewers if the team wants manual approval.

## Branch strategy

- `main` = production; feature branches + PRs; PR checks required (branch protection).
- Optional `staging` branch/environment deployed automatically for QA.
- Database migrations reviewed in PRs (expand/contract — DB-05).

## Dependency updates

Dependabot or Renovate for Composer, npm, GitHub Actions, Docker base images — weekly, grouped minor/patch; majors reviewed manually with the relevant skill's upgrade notes.

## Secrets in CI

GitHub Actions secrets for registry, deploy webhooks; never print env; tests use `.env.example` + generated key; no production credentials in CI.
