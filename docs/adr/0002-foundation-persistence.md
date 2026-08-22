# ADR 0002: Foundation persistence

> Historical note: this ADR describes the original six-table persistence increment.
> ADR 0003 and ADR 0004 extend the accepted foundation to twelve tables; the current
> canonical table set is enforced by `tests/Architecture/verify-foundation.php`.

- Status: Implemented for Gate 1
- Date: 2026-08-02
- Target: JoomLeague 6.2.0

## Decision

The first canonical persistence increment contains six tables with equivalent
MariaDB/MySQL and PostgreSQL semantics:

- `sport_profile` stores stable profile identity and translation keys.
- `sport_profile_version` stores an immutable profile version, its exact JSON
  payload and SHA-256 checksum.
- `sport_type` stores a local administrator-managed sport definition linked to
  the profile version from which it was created.
- `migration_batch` identifies one resumable source import.
- `migration_record` gives every source row a deterministic migration outcome.
- `migration_issue` records reviewable migration problems and their resolution.

Bundled profiles currently declare `schema_version: 1.0-transitional`. This
label is deliberate: the KSM profile payload is preserved without lossy
normalisation, but it is not yet the final public profile schema.

The installer synchronises bundled profiles transactionally and idempotently.
Reusing a profile code and semantic version with different payload bytes is an
error. A changed profile must receive a new semantic version so projects can
remain bound to historically correct rules.

## Portability rules

- Application JSON is stored as text on both drivers so the original payload
  and checksum remain stable.
- Driver-specific identity and text types may differ, while table semantics,
  keys and relationships remain equivalent.
- Fresh-install SQL and the latest foundation update SQL are tested for exact
  equality per driver.
- Runtime writes use Joomla's Database API and bound values.

## Deferred work

This increment does not yet define projects, participants, teams, matches,
score values, standings records or migration adapters. Those entities require
the final profile-schema stress test and legacy fixture inventory before their
constraints are accepted.

## Idempotent profile binding upgrade

The composite sport-type/profile key is ensured in installer `preflight` after inspecting driver-specific table metadata. It is not added unconditionally by an update SQL file. This keeps upgrades from the early foundation schema possible while allowing an interrupted or repeated Joomla update to recover without a duplicate-key failure on MariaDB or PostgreSQL.
