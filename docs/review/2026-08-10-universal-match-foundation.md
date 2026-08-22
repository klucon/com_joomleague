# Universal match foundation

## Result editor foundation

- `MatchResultRepository` now performs transactional replacement and canonical reads of complete result trees.
- Reads reject multiple roots, invalid roots, orphaned or unreachable segments, cycles, repeated references and non-object metadata.
- Exact decimal values remain strings on both supported database drivers.
- `MatchResultEditorSchemaBuilder` derives the editor mode and hierarchy from profile capabilities, never from a sport code. Coverage is 8 numeric, 4 nested, 2 time and 1 decision profile.
- `MatchResultEditorContext` resolves the match, project, round, immutable profile version, ordered participants, editor schema and current result behind one UI-facing API.
- Joomla's PostgreSQL driver may mutate variables passed to `bind()` by reference. Values crossing a later strict-type method boundary must therefore be explicitly recast.
- The integration fixture passes on persistent MariaDB and PostgreSQL installations and verifies failed-replacement rollback.
- The first `matchresult` Joomla MVC view provides a read-only, profile-driven overview and normalized score-tree inspection without custom CSS or JavaScript. Match lists expose it through a dedicated Joomla toolbar-style icon.
- The view enforces `core.manage`; its Close action is a deterministic Joomla toolbar link back to the owning round rather than browser history or a redundant write controller.
- The reusable browser fixture and Playwright scheduling workflow verify creation, result-view navigation, two Joomla tabs, empty-result state, return routing and cleanup. No project, stage, round, match or competition test rows remain afterward.

Date: 2026-08-10

## Scope

- Added project-owned matches scoped through a stage and round.
- Added generic match participants linked to project entries.
- Added Joomla administrator CRUD for match scheduling.
- Added navigation from a round to its matches.
- Stored scheduled timestamps in UTC and displayed them in match, project, or Joomla system timezone order.
- Kept contest type, status, participant role, and result status as profile-defined extensible codes.

## Deliberate boundary

The current bundled sport profiles use the transitional 1.0 schema. Score storage, score editing, home/away semantics, periods, sets, legs, race times, and result calculation are therefore not part of this increment. Those structures must be derived from profile schema 1.1 rather than embedded in the generic match table.

## Database design

- `#__joomleague_project_match` verifies project, stage, and round ownership through a composite foreign key.
- `#__joomleague_match_participant` accepts any project entry and does not assume two teams.
- MariaDB and PostgreSQL definitions and update scripts are kept equivalent.
- Database schema anchor: `6.2.0-2026081004`.

## Verification

## Next dependency

Upgrade and validate the bundled profiles against profile schema 1.1 before implementing universal score components and result engines.
