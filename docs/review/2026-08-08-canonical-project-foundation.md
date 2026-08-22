# Canonical project foundation - implementation review

Date: 2026-08-08

## Scope

This increment establishes the canonical project aggregate for JoomLeague 6.2.0.
It deliberately implements the persistence and read model before write actions, so
the migration and validation contracts can be reviewed before the administrator UI
is allowed to create data.

## Implemented

- Added reusable `competition` and `season` entities.
- Added `project` as a binding of competition, season, sport type and an exact,
  immutable sport profile version.
- Added sparse project rule overrides in `project_rule_config`.
- Added sparse project template overrides in `project_template_config`.
- Added matching fresh-install and incremental SQL for MariaDB/MySQL and PostgreSQL.
- Added uninstall ordering that removes dependent project configuration first.
- Added a project template resolver using the existing five-layer template cascade.
- Replaced the Projects placeholder with a read-only administrator overview backed
  by the canonical tables.
- Added only `en-GB` source language keys and Joomla/Bootstrap presentation classes.

## Integrity guarantees

- A project cannot reference a missing competition or season.
- The composite foreign key `(sport_type_id, profile_version_id)` prevents a project
  from combining a sport type with a profile version assigned to another type.
- Existing projects retain their exact profile version when a newer profile version
  is published.
- Season and project end dates cannot precede their start dates.
- The automatic round-advance interval cannot be negative.
- Rule and template overrides are deleted with their project; shared competition,
  season and sport profile records are protected by restrictive foreign keys.

## Migration contract

The source-to-target mapping is documented in:

- `docs/migration/project-field-matrix.md`
- `docs/adr/0004-canonical-project-aggregate.md`

Legacy football-specific project columns become validated profile rule overrides.
Legacy template inheritance is flattened into sparse project overrides. Source
identity, source payload and conversion issues remain traceable through the existing
migration batch, record and issue tables.

## Deliberately deferred

- Project create/edit/delete and publish actions.
- Profile-schema validation and checksum generation on write.
- Canonical team, project-team, round and favourite-team relationships.
- The executable legacy project migration adapter and reconciliation report.
- Editing project-level template overrides from the Templates screen.

These are not missing from the data model by accident. Enabling writes before the
profile rule schema and migration reconciliation are accepted would allow invalid or
irreversible data to enter the new foundation.

## Verification record

The following checks passed:

- `php tests/Architecture/verify-foundation.php`
  - 15 profiles
  - 12 matching MariaDB/MySQL and PostgreSQL tables
  - cumulative update/fresh-install parity
  - canonical foreign keys and check constraints in both install and update SQL
  - 817 `en-GB` keys, 18 menu views and 6 template definitions
- `php tests/Unit/verify-template-resolver.php`
  - all five configuration layers and all six definitions
- PHP syntax validation for every administrator PHP file
- XML validation for the manifest and component configuration
- ZIP integrity validation
- Joomla extension upgrade on MariaDB and PostgreSQL
- database schema version `6.2.0-20260808.1` on both drivers
- all five canonical constraints present in both live test databases
- authenticated Projects runtime request on both database drivers returned HTTP 200
- no PHP warning, fatal error or untranslated component constant in either response
- no application warning/error in either container log during deployment and checks

Package:

- `dist/com_joomleague-6.2.0-dev.zip`
- SHA-256: `b1c507cd6c8611f49708c7c158402191c930308cc4a8946cd85414287d88abb2`

MariaDB review URL:

- <https://joomla62.klucon.cz/administrator/index.php?option=com_joomleague&view=projects>

The PostgreSQL test instance was verified through its loopback endpoint on port
`18372`. Temporary runtime-check administrator accounts were removed immediately
after each authenticated request. No production or `fotbal2` environment was changed.

## External review