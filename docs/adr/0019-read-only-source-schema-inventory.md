# ADR 0019: Read-only source schema inventory

- Status: Implemented as migration foundation
- Date: 2026-08-10
- Target: JoomLeague 6.2.0

## Context

Historical JoomLeague releases and the new canonical model reuse table names
such as `#__joomleague_project`. A table-name check, installed extension
manifest or Joomla schema version cannot safely identify the meaning of those
tables. Interrupted upgrades may also leave canonical and historical columns in
the same database.

## Decision

Migration discovery begins with a read-only inventory of JoomLeague table and
column names. A pure classifier returns one of four states:

- `canonical`: canonical JoomLeague 6.2 signatures are present;
- `legacy`: historical project, team assignment and roster signatures are present;
- `mixed`: canonical and historical signatures coexist;
- `unknown`: evidence is insufficient for a safe classification.

The result includes confidence, the exact evidence used and conservative
historical-version candidates. It does not infer an exact historical release
from schema shape alone. Exact source detection requires a later adapter to
inspect version rows and representative data signatures.

The administration screen exposes this inventory without migration actions.
No data rows, payloads or identities are read by this increment, and no database
writes occur.

## Safety properties

- Physical table names come only from the database driver's own table list.
- Column metadata comes from Joomla Database API metadata methods.
- The classifier has no database dependency and is covered by canonical,
  legacy, mixed and unknown fixtures.
- A mixed result is never treated as a migration-ready legacy source.
- Unknown and ambiguous sources fail closed until a reviewed adapter exists.

## Next increment

Add a source-version inspector that reads only allowlisted version tables and
returns a version claim with its provenance. It must be followed by fixture-led
entity adapters and deterministic migration-record outcomes; it must not mutate
historical tables in place.
