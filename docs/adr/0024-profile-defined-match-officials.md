# ADR 0024: Profile-defined project and match officials

## Decision

Officials are independent project actors, not competition participants and not members of either match participant. `project_actor_role` registers a canonical person or team in one official role defined by the project's immutable sport profile. Optional validity dates control availability for individual matches.

`match_actor_role` is the historical match assignment. It stores the canonical actor reference, role code, person type and a display-name snapshot. `source_project_actor_role_id` records provenance but uses `ON DELETE SET NULL`, so removing a current project registration never removes or renames an historical match assignment.

## Universal boundary

The database uses an actor discriminator with mutually exclusive `person_id` and `team_id`. This supports individual referees, umpires, delegates and timekeepers as well as historical competitions where a team performs an official duty. Runtime code never branches on a sport code.

The current administration exposes positions whose profile-defined `person_type` is `official`. Both role code and person type are revalidated against the immutable project profile before each write. Future profile schema versions may declare additional match-duty person types without changing either table.

## Integrity

- Composite match/project ownership prevents cross-project match rows.
- Project assignment writes reject invalid actors, invalid profile roles, malformed dates and overlapping periods for the same actor and role.
- Match assignment writes require the source registration to belong to the match project and to be active on the match's local calendar date.
- A source registration can be assigned only once to one match.
- All mutations use Joomla CSRF and component ACL checks and execute transactionally.

## Database parity

MariaDB/MySQL and PostgreSQL define equivalent actor checks, date checks, unique constraints, indexes, foreign keys and delete behavior. Schema anchor: `6.2.0-2026081105`.
