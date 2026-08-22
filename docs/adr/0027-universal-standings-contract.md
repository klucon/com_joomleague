# ADR 0027: Universal standings contract

## Status

Accepted as the implementation direction for the Joomla 6.2 development baseline.

## Context

The existing profile `standings` blocks describe visible columns and preferred sorting, but several are not executable contracts. A final score alone also cannot distinguish a regular, extra-time or shootout decision, and nested sports award points from score patterns. Race classifications use rank, status and lower-is-better values instead of win/draw/loss.

## Decision

Every profile will receive a validated `standings.calculation` contract. Runtime code consumes that contract and never branches on the sport code.

The contract has four explicit parts:

1. `mode`: `head_to_head` or `classification`;
2. `metrics`: named exact aggregations sourced from root scores, segment values, segment wins, participant statuses or canonical match statistics;
3. `awards`: ordered outcome, decision-segment, score-pattern, threshold or rank rules, with an explicit `none` mode where a sport does not award table points;
4. `ordering`: deterministic metric clauses followed by the canonical entry name and persistent entry ID.

Conditions use a closed data grammar. They are JSON data, not SQL, PHP expressions or executable callbacks. Numeric arithmetic uses signed decimal strings with at most nine fractional digits; PHP floats are forbidden.

Head-to-head processing derives each participant perspective from the final root values. The deepest populated direct result segment may identify the decision method. Nested segment wins and values remain separate facts. Classification processing reads final participant status, rank and root value. Profile-defined status precedence decides unranked classifications.

Scopes are filters over one canonical calculation, not separate sport implementations. Total/home/away use match participant slots or role codes. Overall/category/gender/team scopes require explicit project-entry metadata mappings in the profile; they are never inferred from translated labels.

## Materialization

The engine first produces an immutable in-memory result. A later persistence step stores:

- one calculation snapshot with profile checksum, input checksum, scope, state and audit metadata;
- ordered rows bound to project entries;
- exact metric values and display metadata.

A completed snapshot is immutable. Publishing a new current snapshot is transactional. Failed recalculation never replaces the previous current snapshot.

## Delivery order

1. contract validator and pure calculation stress tests;
2. migration of all 15 development profiles to the executable contract;
3. snapshot persistence with MariaDB/PostgreSQL parity;
4. administrator read and explicit recalculation workflow;
5. incremental invalidation and scheduled recalculation.

No legacy migration work is included in this phase.

## Safety

- Only final results declared eligible by the profile are consumed.
- Project, stage, match, participant and entry ownership is validated before calculation.
- Recalculation requires Joomla edit permission and CSRF protection.
- Limits bound matches, rows, metrics and condition depth.
- No existing result, event or statistic row is modified by standings calculation.

## Review note
