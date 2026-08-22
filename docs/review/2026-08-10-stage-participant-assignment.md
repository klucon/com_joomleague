# Stage participant assignment

Date: 2026-08-10

## Design

Project participants remain the single canonical records. A stage chooses one of two modes:

- `inherit_project`: all project participants are effective participants and no assignment rows are stored;
- `explicit`: only entries recorded in `#__joomleague_stage_entry` participate in the stage.

The pivot stores `project_id` and uses composite foreign keys to both the stage and project entry. This prevents cross-project assignment even when data is written outside the Joomla model. The pivot also reserves stage-specific ordering, seeding and metadata without duplicating project participants.

## Administration

The Stages list has a Joomla icon action for Manage stage participants. The assignment view uses Joomla toolbar actions, switcher controls, table styling, CSRF protection and component ACL. It shows teams, individuals and abstract groups through the same project-entry contract.

No custom CSS, JavaScript or visible hardcoded text was introduced. Technical database errors are written to the Joomla log and are not exposed in the administrator response.

## Verification

- Foundation verifier: 15 profiles, 25 tables on both database drivers and 1515 en-GB keys.
- Clean package installation and Joomla Database Checker passed on MariaDB and PostgreSQL at update anchor `6.2.0-2026081002`.
- Browser workflow verified inherited selection, explicit selection of one from two participants and return to inherited mode.
- Database verification confirmed that inherited mode stores zero stage-entry rows.
- Persistent MariaDB and PostgreSQL test installations were upgraded from the preceding schema.

Backup before deployment:

`/mnt/disk-b/server-backups/joomla62/20260810-212740`

## Next step

Add rounds owned by a project stage. A simple league can use one stage; group and knockout competitions can create independent round sequences under their respective stages. Migration remains intentionally deferred until the administration model is complete.
