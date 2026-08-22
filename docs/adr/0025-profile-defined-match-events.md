# ADR 0025: Profile-defined match events

## Decision

A match event is an independent historical timeline record whose definition comes from the immutable sport profile bound to the project. Runtime code never branches on a sport code. The stored event contains the profile event code, translation key and complete event-definition snapshot; a materialized event-type ID is optional provenance and uses `ON DELETE SET NULL`.

An event may reference one generic match participant, a primary and secondary lineup person, one assigned match actor-role and one score segment. Canonical person IDs and display-name snapshots preserve actor identity if a lineup assignment is later removed. Every referenced participant, lineup member, official and score segment is validated against the same match before writing.

System events cannot have a participant or actor. Profile definitions requiring a second person require two distinct lineup members belonging to the same match participant. A lineup person and an official cannot simultaneously be the primary actor.

## Timeline

Timeline placement is profile-neutral:

- `phase_code` references a profile score-segment type;
- `phase_sequence` identifies a repeated period, set, lap, map or other phase;
- `clock_value` is an exact decimal paired with an open profile/domain `clock_unit` string;
- `score_segment_id` optionally links an instantiated result segment;
- `occurred_at` optionally stores an absolute timestamp for imported or externally timed events;
- `sequence_number` provides stable match-wide ordering.

No field assumes football minutes or a two-team contest.

## Feature boundaries

Match events do not mutate `match_result`, score values, standings or lineup state. `match_lineup_change` remains the canonical substitution history. A profile event named substitution may be recorded as timeline information, but it does not replace or derive the canonical ordered lineup change. Score-affecting profile metadata is retained for future validation/derivation work; the first administration intentionally does not update results implicitly.

## Integrity and concurrency

MariaDB/MySQL and PostgreSQL enforce equivalent match/project ownership, participant ownership, snapshots, person references, non-negative clocks and paired clock units. Application validation covers profile membership and cross-match ownership for nullable provenance references.

The match-wide sequence is allocated using `MAX(sequence_number) + 1` and protected by a unique key. Concurrent writers can therefore cause one transaction to fail cleanly and retry manually; they cannot create duplicate ordering or partial data. A future live-scoring workflow should add portable serialization or an explicit retry policy before supporting multiple simultaneous event operators.

## Administration

The match list opens a Joomla-native Match events workspace. It supports profile-defined event selection, participant/person/official assignment, optional phase and exact clock data, values, notes, timeline listing and owned removal. Controllers enforce CSRF and component `core.edit` ACL.

## Database parity

Both supported drivers define the same table, columns, foreign keys, checks and indexes. Schema anchor: `6.2.0-2026081106`.
