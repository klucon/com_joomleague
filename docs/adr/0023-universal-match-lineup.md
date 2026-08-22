# ADR 0023: Universal match lineup

## Decision

A match lineup is a historical snapshot attached to one generic match participant. It is not stored on a team, in a football-specific roster table or inside result metadata.

`match_lineup_member` stores the canonical person, person type, role, shirt number, lineup status and captain flag used for that match. `source_entry_member_id` records provenance from the project roster but is nullable and uses `ON DELETE SET NULL`; removing a current membership must not erase a historical lineup.

The `(match_participant_id, match_id)` foreign key guarantees the participant belongs to the match. Assignment additionally verifies that the source membership belongs to the participant's project entry. A person can occur only once per match participant.

## Profile boundary

Runtime code never branches on a sport code. It validates member person types and roles against the immutable project profile, applies the profile's `players_on_field` limit and permits a captain only when the profile declares captain support. Player statuses are `starter`, `substitute` and `available`; staff statuses are `active` and `available`.

Only memberships valid on the match's local calendar date are offered. Match timezone overrides project timezone, which overrides Joomla system timezone.

## Ordered player changes

`match_lineup_change` records substitutions against two stable lineup snapshots belonging to the same match participant. Composite foreign keys prevent cross-match and cross-participant references. The ordered sequence is replayed from starters: the outgoing player must be active and the incoming player inactive at every step. This permits profile-neutral re-entry while rejecting impossible histories.

Substitution availability and limits come exclusively from the immutable project profile. Optional phase codes must reference profile score segment types. Clock values remain exact decimals and carry a profile-specific machine-readable unit; runtime code does not assume football minutes.

## Administration

The match list opens one lineup workspace. Administrators first select a generic match participant, then manage Players, Staff and Substitutions through Joomla tabs. Assignments and removals are transactional. A substitution can be removed only when replaying the remaining sequence still produces a valid active lineup.

## Database parity

MariaDB/MySQL and PostgreSQL define equivalent columns, unique constraints, indexes and foreign keys. Schema anchor: `6.2.0-2026081104`.
