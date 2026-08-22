# ADR 0004: Canonical project aggregate

- Status: Accepted for Gate 1 implementation
- Date: 2026-08-08
- Target: JoomLeague 6.2.0

## Context

Historical JoomLeague stores a project identity, league and season references, football-oriented match rules, current-round selection, favourite-team presentation, template inheritance, media, integration flags and arbitrary extension data in one `project` row. That shape cannot express all bundled profiles without adding more sport-specific columns.

The canonical model must support timed team sports, set-based contests, races and future profile types while preserving every meaningful value migrated from JoomLeague 0.93, 1.5, 2.5, 3 and supported V6 alpha releases.

## Decision

The project increment contains five entities.

### Competition

`competition` is the reusable identity historically stored in `league`. It owns the long, middle and short names, aliases, country or federation context and optional external identifiers. It does not contain season-specific rules.

### Season

`season` is a reusable time period with an optional date range. A project references exactly one season. Migration adapters create an explicit placeholder and issue when a historical project has a broken zero or missing season reference; canonical rows never use magic ID zero.

### Project

`project` is one concrete competition edition. It references a competition, season, local sport type and the exact immutable sport-profile version used to interpret its rules.

The project stores only stable identity and scheduling context:

- UUID, human name, alias and optional internal/external codes;
- competition and season;
- local sport type and immutable profile version;
- profile-declared project type;
- optional timezone override, start/end date and default start time;
- current-round selection policy and automatic advance delay;
- description, picture, lifecycle state and Joomla state fields.

The sport type and profile version are protected by one composite foreign key. A project cannot accidentally bind a sport type to a different profile version, and a used sport type cannot silently switch profile versions.

An empty project timezone means inheritance from the component override and then Joomla Global Configuration. Stored match instants will later use UTC plus their source timezone context; `is_utc_converted` is migration state, not a permanent project-domain field.

### Project rule configuration

`project_rule_config` stores one sparse JSON override object and checksum. It may contain only profile fields explicitly declared project-overridable by the accepted profile schema. Match duration, periods, extra time, scoring points, set rules and similar sport behavior do not become project columns.

Effective rules resolve as:

1. immutable profile version;
2. local sport-type overrides;
3. sparse project rule overrides.

### Project template configuration

`project_template_config` stores sparse frontend overrides by `project_id` and `template_code`. It completes the resolver layer accepted in ADR 0003. Missing rows mean full inheritance from template definition, bundled profile defaults and local profile overrides.

## Integrity and lifecycle

- All canonical primary keys are database-native numeric identities; UUIDs provide stable public/import-safe identities.
- `competition`, `season`, `sport_type` and `profile_version` deletes are restricted while referenced.
- Deleting a project cascades only to its owned rule and template configurations.
- Human names are not globally unique. Federations may reuse the same competition or season name.
- JSON is canonical UTF-8 text on both database drivers. The application sorts object keys before calculating SHA-256 checksums.
- Unknown configuration keys, invalid value types and unsupported project types fail before database writes.
- Joomla publishing state and project lifecycle state remain separate concepts.

## Explicitly deferred relationships

- Favourite teams require the canonical project-participant/team assignment table.
- `current_round_id` requires the canonical round or stage table.
- Project positions, officials and participants require their canonical identity and assignment tables.
- Legacy `master_template` does not become a self-referencing project relation. Migration resolves and flattens its effective settings into sparse overrides, avoiding inheritance cycles.
- Legacy component extensions become explicit integrations later; raw extension names remain in migration provenance until an adapter exists.

## Database portability

MariaDB/MySQL and PostgreSQL implement equivalent tables, unique keys, indexes, foreign keys and delete behavior. JSON remains text so checksums are stable across drivers. Driver-specific identity and timestamp syntax may differ without changing semantics.

## Consequences

### Positive

- No football-specific rule columns are required.
- Projects remain historically bound to the rules under which they were created.
- Template and sport-rule inheritance have independent, testable persistence.
- Broken legacy references become visible migration issues instead of magic zero IDs.

### Negative

- Migration must parse historical comma-separated points and other overloaded fields.
- Existing V6 code cannot query the canonical project table as if it were the JL3 table.
- Full project editing waits for accepted competition, season, stage and participant workflows.
