# ADR 0026: Profile-defined match statistics

## Status

Accepted for the Joomla 6.2 development baseline.

## Context

Sport profiles define statistics with open scope and value-type codes. The administration must support team, person, goalkeeper and race-participant values without branching on a sport code. Event-derived and calculated statistics must retain one authoritative source.

## Decision

`#__joomleague_match_statistic_value` stores match-owned statistic values and immutable snapshots of the profile definition, target and optional score segment. A unique match/statistic/target/segment identity makes repeated manual saves idempotent.

Only profile statistics with `source` equal to `manual` or `manual_or_import` are writable in the manual workspace. Event, calculated and import-owned definitions are visible but read-only.

Participant targets are accepted when the statistic scope is `participant` or matches the project-entry kind. Person targets are accepted when the scope matches the lineup member person type, role code or profile-defined lineup group. These are generic profile contracts, not fixed enums.

Integers, decimals and percentages use exact decimal storage. Durations use exact milliseconds through `MatchResultDuration`. Historical person values retain the canonical person and snapshots when a lineup assignment is removed. The target check therefore uses `person_id` as the discriminator; `lineup_member_id` is an optional historical source reference.

## Consequences

- one workflow supports every bundled profile without sport-specific PHP branches;
- event and calculated values cannot be overwritten manually;
- historical values remain intelligible after profile, name, segment or lineup changes;
- concurrent first inserts can produce a unique-key conflict, but cannot create duplicate values;
- standings may consume these canonical statistics later but remain a separate domain.
