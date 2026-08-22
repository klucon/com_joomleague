# Sport profile result contract 1.2 implementation

## Scope

The development profile set now uses schema `1.2.0` and profile version `1.3.0`. All 15 bundled profiles declare the same result primitives regardless of whether the contest uses goals, points, sets, time, pins, classification or judges' decisions.

## Implemented contract

- `match.score.segment_types` is used by every score type.
- Segment types form a validated acyclic graph through `parent_code`; the implicit root is `result`.
- Every segment declares its stable `ordinal`, repeatability and optional expected/maximum count and editor condition.
- Root aggregation is declared as `{mode, from, final_only}`.
- Result lifecycle is the fixed core vocabulary `draft`, `in_progress`, `final`.
- Outcome and participant-status vocabularies are declared by each profile.
- Canonical final values, ranks and participant result statuses remain in score values.
- Operational participation status has a dedicated nullable `match_participant.participation_status` column.
- Optional `editor_control` declarations distinguish persisted value types from their administration control without sport-specific PHP branches.
- MMA uses a status/rank result root, value-less judge containers and numeric round cards.

## Database guarantees

- Score segments persist `segment_type_ordinal` and are read in `(ordinal, sequence, id)` order.
- MariaDB uses a generated nullable root marker plus a unique `(match_id, root_marker)` key.
- PostgreSQL uses a partial unique index on `match_id WHERE parent_id IS NULL`.
- Both variants prevent concurrent creation of two root segments for one match.
- Existing `result_status` and `result_rank` participant columns are retained. No column or stored data was removed.

## Compatibility layer

Legacy profile keys such as top-level `match_structure`, `result_rules`, `scoring_model`, `project_types`, nested `score.levels` and score support flags remain present because current project-rule editing still consumes them. New result code consumes the canonical `match.*` contract. Removing or converting these aliases requires a separate, explicitly approved migration.

## Development synchronization

`Com_JoomleagueInstallerScript::DEVELOPMENT_PROFILE_SYNC` is enabled for the `6.2.0-dev` branch. It updates the single active bundled profile-version row in place only while no project references that row. Referenced versions remain immutable and a new version is inserted instead, preserving the meaning of existing projects and results. Before the first public release this switch must be disabled to restore immutable published profile versions unconditionally.

## Verification

- All 15 bundled profiles pass `SportProfileSchemaValidator`.
- Result editor schema coverage: 8 numeric, 4 nested, 2 time and 1 decision profile.
- Stress payloads cover football periods, tennis set/game/point hierarchy, running gun/chip/split timing and MMA judge/round scorecards.
- Tests reject incompatible value/editor control pairs and verify the complete MMA control tree.
- The foundation test confirms 31 tables for both MariaDB and PostgreSQL and complete en-GB language-key coverage.

## Deferred work

- Detailed shootout-attempt recording beyond the aggregate shootout result.
- Runtime evaluation of `condition_code`; it is currently an editor hint.
- Executing `derive` aggregation rules; schema validation and storage are implemented first.
- Explicitly approved removal of compatibility aliases and deprecated participant result columns.
