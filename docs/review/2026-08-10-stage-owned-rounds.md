# Stage-owned rounds

Date: 2026-08-10

## Design

`#__joomleague_project_round` is the universal scheduling unit below a project stage. Every round has mandatory `stage_id` and `project_id` ownership. A composite foreign key guarantees that the selected stage belongs to the same project.

Round names remain free display text and `round_type` remains an extensible code. This supports matchdays, group rounds, knockout phases, heats and other sport-defined concepts without a football-specific enum. Codes and sequence numbers are unique inside a stage, allowing parallel groups to use the same codes independently.

## Administration

- Each stage has a Joomla icon action opening its rounds.
- The rounds list provides Joomla search tools, lifecycle and publication filters, pagination, ordering, check-in and standard toolbar actions.
- The editor has Details, Description and Publishing tabs.
- Description is a dedicated full-width editor row.
- Stored stage and project ownership cannot be changed by tampering with an edit request.
- Rounds are no longer shown as a planned project-panel feature.

No custom CSS, custom JavaScript or visible hardcoded text was added.

## Verification

- Foundation verifier: 15 profiles, 26 tables on MariaDB and PostgreSQL, 1551 en-GB keys.
- Clean installation and Joomla Database Checker passed on both database drivers at schema anchor `6.2.0-2026081003`.
- Persistent MariaDB and PostgreSQL test installations upgraded successfully.
- Browser verification passed stage creation, round creation, date persistence, three-tab editor layout, rename and deletion.
- Fixture data was removed after verification.

Backup before deployment:

`/mnt/disk-b/server-backups/joomla62/20260810-222859`

## Next step

Add matches owned by a round, using project entries as the generic contestants and profile-driven score storage. Match design must support head-to-head, multi-participant races and nested set/period scores before its schema is finalized.
