# Project participant CRUD review

- Date: 2026-08-10
- Target: JoomLeague 6.2.0 development branch
- Deployment: Joomla 6.2 test stack only

## Delivered

- Replaced the read-only project participant list with native Joomla New, Edit
  and Delete toolbar workflows.
- Added a tabbed participant editor for competition metadata and publishing.
- Participant kinds are loaded from the immutable profile version attached to
  the project. The shared editor supports team, person and named-group entries
  without sport-specific branching.
- Added server-side profile validation, target existence checks, lifecycle and
  length validation, UUID/audit preparation and project-scoped ordering.
- Existing entries retain their stored project ID during edits; a submitted
  hidden field cannot move an entry across project boundaries.
- No custom CSS and no database-schema changes were introduced.

## Security boundary

- Joomla FormController/AdminController provide session-token validation.
- Create, edit and delete actions require the corresponding component ACL.
- Client-side `showon` behavior is presentation only. The model and table repeat
  all participant-kind and target validation on the server.
- Database identifiers are fixed in source and scalar identifiers use integer
  filtering or typed bindings.

## Verification

- PHP lint, XML validation and the foundation suite passed with 15 profiles, 23
  equivalent MariaDB/PostgreSQL tables and 1433 en-GB language keys.
- Clean package installation passed on MariaDB and PostgreSQL.
- Repeated package updates and Joomla Database Checker passed on both persistent
  test instances.
- Playwright created, edited and deleted a temporary team participant through
  the real administrator UI. Fixture cleanup left no project or entry rows.
- Application logs contained no related warning, deprecation or fatal error.
- Test backup: `/mnt/disk-b/server-backups/joomla62/20260810-193332`.

## Review limitation

## Recommended next increment

Implement project-entry membership administration. Team/group projects need a
profile-aware roster list and editor for players and staff; individual-entry
profiles must remain valid without exposing an irrelevant roster workflow.
