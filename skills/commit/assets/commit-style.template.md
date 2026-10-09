# My commit style

<!--
  Read by the `commit` skill before every commit. Edit freely, or tell Claude
  "from now on …" and it updates this file. Global: ~/.claude/commit-style.md
  Per-repo override: <repo>/.claude/commit-style.md
-->

## Format
- `type(scope): Summary.`
- Types: feat, fix, refactor, docs, test, ci, chore
- Scope: area touched, lower-case kebab-case; omit when the change has no single area. Reuse scopes from `git log`.

## Subject
- Tense: past tense, starting with a verb (Added, Fixed, Removed, Renamed, Moved…)
- Capital letter after the colon: yes
- Ends with a period: yes
- Max length: ~72 characters
- Language: English

## Body
- Only when the subject can't carry it: breaking changes, migrations, new env vars, required steps elsewhere, non-obvious reasons.
- A few short lines or `-` bullets; no paragraphs; no file-by-file lists.

## Cadence
- Commit as you go: after each finished step (a page created, a button added, a migration, an Action, a test), not one big commit at the end.
- Several commits in one file is fine, e.g. page created, then filter added, then button added.
- Not too much: skip typo/format tweaks (fold into the next commit), never commit knowingly broken code, no WIP commits. Roughly 1-4 files and a few dozen lines per commit.
- Commit locally automatically; never push unless asked.
<!-- Prefer fewer commits? Change to: "Commit once per finished feature, grouped by area." -->

## Splitting
- Group by area/feature (what the change is for), not by file type.
- Related backend + frontend for one small feature can be one commit.

## Trailers & attribution
- None. No Co-Authored-By, no Signed-off-by, no "Generated with", no AI mentions, no emoji.

## Push
- Ask which branch before every push. Never force-push unless asked.

## Examples
- `feat(dashboard): Added ticket panel overview page.`
- `fix(settings): Kept unsaved changes when switching tabs.`
- `docs: Updated the setup instructions.`

## Don'ts
- `Update files` / `WIP` / `misc changes`
- Emoji anywhere
