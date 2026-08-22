# Club and team name and logo history

## Scope

JoomLeague keeps the current club or team name and logo on the canonical organization row. Historical names and logos are stored separately so normal lists remain fast while dated records are available for migration, reporting and future frontend output.

The implementation applies equally to clubs and teams:

- `#__joomleague_organization_name_history`
- `#__joomleague_organization_media_history`
- Joomla repeatable subforms in the History and Media tabs
- transactional persistence with the parent club or team

## Data guarantees

- Every history row belongs to exactly one club or one team.
- Database foreign keys cascade only when the owning club or team is explicitly deleted.
- The end date cannot precede the start date.
- Name periods cannot overlap other name periods of the same club or team; logo periods follow the same rule independently.
- Open-ended periods are treated as unbounded, and a shared boundary date counts as an overlap.
- Submitted existing row IDs are accepted only when they belong to the edited organization.
- Omitting an existing row from a later submission does not delete it.
- A history row is deleted only when its native Joomla `Remove record` switch is explicitly enabled and the parent form is saved.
- Deletion is restricted by both the history row ID and its owning organization; deleting another club's or team's row is rejected.
- A new, unsaved row marked for removal is ignored.
- Explicit removals are processed before inserts and updates, so replacing an interval in one save does not depend on subform row order.
- Current values and historical values remain separate; saving history does not silently change the current name or logo.

The MariaDB/MySQL and PostgreSQL baseline and update schemas enforce equivalent constraints and indexes. Schema update `6.2.0-2026081102` introduces both tables.

## Verification

- `tests/Architecture/verify-foundation.php` checks the 33-table cross-driver baseline, form wiring, additive controls and repository ownership contract.
- `tests/Integration/organization-history.php` creates fixture-only club and team records, stores multiple histories, updates and explicitly deletes owned rows, rejects foreign updates and deletes, and verifies no-delete-by-omission behavior.
- `tests/Browser/verify-organization-history-ui.cjs` logs into Joomla, opens both editors and verifies translated tabs, native Joomla Add controls and removal switchers.
- The integration fixture removes only the records it created.

Deployment verification completed on MariaDB and PostgreSQL with component schema `6.2.0-2026081102`. The final pre-deployment backup is `/mnt/disk-b/server-backups/joomla62/20260811-190417/`.