# ADR 0022: Sport profile result contract 1.2

## Status

Accepted for implementation during the JoomLeague 6.2 development cycle.

## Context

Schema 1.1 described nested score levels only for `nested_score`. Numeric sports could advertise period, extra-time and shootout capabilities, but the payload validator rejected every child below the final result. Time results could not represent sessions, laps, splits, gun time and chip time. Decision results could not represent judge cards and rounds.

The editor schema also supplied hard-coded lifecycle and outcome lists. Participant result status and rank existed both on the match participant and on score values, creating two possible sources of truth.

## Decision

All score types use `match.score.segment_types`. The root segment named `result` is implicit and is not repeated in the profile. Every declared segment type contains:

- `code` and translatable `name_key`;
- `unit` and `value_type`;
- `parent_code`, using `result` for a direct child of the root;
- stable positive `ordinal` among types sharing one parent;
- `repeatable` and optional positive `expected_count` or `maximum_count`;
- optional open `condition_code`, which is an editor hint and not a hard-coded rules engine.

The root score definition and each segment type may also declare `editor_control`. It is a presentation contract, not persisted result data:

- `number` for integer or decimal values;
- `duration` for duration values;
- `text` for free structured text;
- `status_rank` when participant status and rank are the complete value;
- `none` for structural containers whose values belong only to child segments.

The schema validates compatibility with `value_type`. When omitted, the editor derives the conventional control from `value_type`.

The segment graph must be acyclic, connected to `result`, and have unique codes and sibling ordinals. Runtime payloads may only instantiate paths declared by this graph. A participant has at most one value in one segment. Parallel measures such as gun and chip time therefore use distinct sibling segment types.

The score contract may define one aggregation rule:

```json
{
  "aggregation": {
    "mode": "validate",
    "from": ["period", "extra_time"],
    "final_only": true
  }
}
```

Supported modes are `none`, `validate`, and `derive`. Validation or derivation only applies to the listed segment types. Shootout values are separate from a goal total unless explicitly listed. `final_only` permits incomplete Apply operations while a match is in progress.

In `derive` mode the runtime replaces root numeric values with the exact decimal sum of the declared source segments for each participant. Existing participant status, rank and metadata remain intact. No floating-point arithmetic is used.

Result lifecycle is a small component-owned vocabulary: `draft`, `in_progress`, and `final`. Match cancellation, postponement and abandonment remain match lifecycle states.

Sport profiles separately declare:

- `match.outcome_codes` for facts about the contest result;
- `match.participant_status_codes` for facts about one participant's result.

Each definition has a code and `name_key`. These vocabularies are extensible profile data, not PHP enums. Codes such as `ko` and `decision` are outcomes; codes such as `dns`, `dnf`, and `dsq` are participant statuses.

## Persistence

The root `match_score_value` is the canonical final value, participant result status, and rank. Child values are segment-specific. Standings consume root values.

`match_participant.participation_status` describes operational participation independently of a result. Existing `result_status` and `result_rank` columns are retained as deprecated compatibility fields during development. They are not removed without a separate approved migration decision.

`match_score_segment.segment_type_ordinal` persists profile ordering. Segments sort by ordinal, sequence, and persistent ID. A portable generated root marker and unique constraint ensure at most one root per match on both MariaDB and PostgreSQL; the repository still validates exactly one root for every stored result.

## Profile examples

- Football: `period`, `extra_time`, and `shootout` below `result`.
- Tennis: `set` below `result`, `game` below `set`, and `point` below `game`.
- Running race: sibling `gun_time`, `chip_time`, `split`, and `lap` measures.
- Motorsport: `session` below `result` and `lap` below `session`.
- MMA/boxing: a `status_rank` root, value-less `judge` containers below `result`, and numeric `round` values below each judge.

## Development synchronization

Bundled profiles are not version-history fixtures before the first public 6.2 release. Synchronization updates the one active bundled profile version in place and retains 15 active / 0 superseded versions. Once immutable public versions begin, this development behavior must be replaced by normal append-only versioning.

Every bundled profile must match the current schema exactly. An older or unknown schema is a hard installation error and must never be silently skipped.

## Consequences

The result editor can be generated from one graph for numeric, nested, time, and decision results. The schema supports later detailed shootout attempts by making shootout repeatable, but the first editor may store only an aggregate shootout segment. Aggregation, participant status, and outcome validation become profile-driven and independently testable.
