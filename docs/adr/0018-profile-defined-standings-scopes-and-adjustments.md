# ADR 0018: Profile-defined standings scopes and adjustments

## Status

Accepted for the JoomLeague 6.2 development baseline on 2026-08-15.

## Context

Standings were executable only for one implicit scope: `total` for head-to-head contests and `overall` for classifications. That made home/away tables sport-specific application logic. Administrative penalties and bonuses also had no canonical persistence model, so changing a table would have required mutating results or generated snapshots.

## Decision

- Every sport profile schema `1.4.0` declares one or more executable `standings.calculation.scopes`.
- A scope contains a stable code and a declarative filter. Schema `1.4.0` supports `always` and `participant_slot`; the engine does not branch on a sport code or translated name.
- Bundled profile version `1.5.0` is the first immutable profile contract with executable scopes.
- Head-to-head profiles always define `total`. Football, ice hockey and volleyball additionally define `home` and `away` because those scopes were already part of their public profile contract. Classification profiles define `overall`.
- Administrative corrections are stored in `#__joomleague_standing_adjustment`. They target a project participant, stage context, scope and non-derived metric and require a non-empty reason.
- `all` is a reserved adjustment scope meaning that the correction applies to every executable scope. It is not a calculated scope itself.
- Adjustments are part of the standings input checksum and are applied before difference and ratio metrics are derived. Existing snapshots remain immutable.
- Projects continue to use their pinned sport profile version. Installing a new bundled version does not rewrite the profile payload of an existing project.
- A pinned profile without executable standings borrows only the calculation contract from the active version of the same profile identity. A pre-`1.4.0` calculation contract is adapted in memory to its former single `total` or `overall` scope. Stored immutable payloads are not changed and no sport-specific inference is performed.

## Consequences

The same calculation service now produces total, home, away and overall views without sport-specific PHP branches. Corrections are auditable, repeatable and cannot silently alter match records. Future scope filters require a schema version change and validator support rather than an ad hoc controller condition.
