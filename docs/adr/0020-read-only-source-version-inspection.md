# ADR 0020: Read-only source version inspection

- Status: Implemented as migration foundation
- Date: 2026-08-10
- Target: JoomLeague 6.2.0

## Context

Schema signatures identify canonical, legacy, mixed and unknown databases, but
they cannot prove an exact historical JoomLeague release. Historical releases
stored version components in `#__joomleague_version`; an upgraded database may
contain only the version written by a later migration, so this evidence must not
be presented as proof of the database's original release.

## Decision

The version inspector runs only for `legacy` and `mixed` schema classifications.
It reads at most 20 rows from `#__joomleague_version`, newest `id` first, and
selects only allowlisted columns. The pure parser requires bounded non-negative
`major`, `minor` and `build` values and accepts only safe revision/channel
suffixes.

The result contains a status, a normalized display version, a conservative
source family and evidence. Canonical schemas return `not_applicable`; unknown
schemas return `unavailable`; absent, malformed or empty historical evidence is
reported without guessing.

## Safety properties

- The inspector never creates, alters or writes a table.
- Table and column identifiers come from Joomla's database metadata API.
- SQL projection is restricted to a fixed column allowlist.
- Query results are bounded to 20 rows.
- Untrusted suffixes never become part of a displayed version.
- Version-family claims remain separate from schema classification.

## Consequence

A detected `1.5.0.a` row means that the inspected database reports that version;
it does not prove that its data did not originate in JoomLeague 0.93. Migration
planning must retain this provenance and use entity-level adapters and
fingerprints before any write phase is introduced.
