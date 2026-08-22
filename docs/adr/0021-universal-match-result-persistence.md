# ADR 0021: Universal match result persistence

## Decision

Match scheduling and result data remain separate aggregates. A result uses three normalized tables:

- `match_result` stores one result header per match, including the profile score type, lifecycle status, optional outcome code and finalization time;
- `match_score_segment` stores an arbitrary hierarchy of profile-defined score levels;
- `match_score_value` stores a participant's value, status and optional shared rank within one segment.

There are no home, away, goal, set, leg or period columns. Profile level codes define the hierarchy. Numeric values use exact decimal storage and cover integer scores, half-points, percentages, milliseconds and penalties. Text values are reserved for genuinely textual profile results, not formatted numeric scores.

## Integrity

Composite foreign keys ensure that parent segments, values and participants belong to the same match. A value must contain at least a numeric value, textual value, status or rank. Ranks are positive and may be shared by multiple participants.

Payload validation limits one replacement to 1,000 segments and 10,000 values in addition to the profile-defined hierarchy depth. Authorization and CSRF checks belong to the Joomla controller boundary; the repository accepts only already-authorized domain input.

The result header does not store one winner. A single winner would not represent draws, tied ranks, team classifications or multiple qualifying participants. Outcome and ranking are resolved from participant values according to the immutable profile contract.

## Metadata boundary

`metadata_json` may retain profile-specific supplementary facts that are not queried as core result data. It must not replace score values, hierarchy, participant status, rank or lifecycle fields.

## Supported shapes

- Football and basketball use a root numeric segment.
- Tennis, volleyball, darts and esports use nested segments of profile-defined depth.
- Running races and motorsport store exact time values and ranks per participant.
- MMA and boxing may nest judge scorecards and rounds while retaining a decision outcome code.

## Repository contract

The write repository replaces one complete validated result tree in a transaction. The read repository reconstructs the same canonical tree without exposing normalized database identifiers. Children are ordered by `sequence_number` and their persistent identifier, while participant values follow match slot order. Exact database decimals remain strings and are never converted through floating-point values.

Reads reject malformed persisted state: multiple or missing roots, a root other than `result`, orphaned segments, cycles, repeated references and non-object metadata. A failed replacement leaves the previous result untouched.

The profile-driven editor consumes a derived schema rather than branching on a sport code. It supports numeric, nested, time and decision results and preserves every nested level declared by the immutable profile version. Optional period, extra-time and shootout sections are capabilities declared by the score contract.

## Verification

Fresh installation and Joomla database structure checks must pass on MariaDB and PostgreSQL. Match participants cascade with their project entries so deleting a project can remove its complete aggregate. Schema anchor: `6.2.0-2026081006`.
