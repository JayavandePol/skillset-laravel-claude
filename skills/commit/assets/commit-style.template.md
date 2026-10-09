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

## Splitting
- Multiple commits over one big commit, grouped by area/feature (what the change is for), not by file type.
- Don't over-split: related backend + frontend for one feature can be one commit.

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
