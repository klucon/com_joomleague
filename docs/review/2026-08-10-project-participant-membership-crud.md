# Project participant membership CRUD review

- Date: 2026-08-10
- Target: JoomLeague 6.2.0 development branch
- Deployment: Joomla 6.2 test stack only

## Delivered

- Added native Joomla list and tabbed edit views for members of a project
  participant.
- The roster workflow is exposed only when the immutable sport-profile version
  declares `entry_model.members_supported` for the participant kind.
- Member person types and role choices come from the attached profile version.
  The role selector reacts to the selected person type and the model repeats the
  same validation on the server.
- Memberships support shirt number, captain status, validity dates, lifecycle
  state, notes, publishing, ordering, UUID and audit metadata.
- Existing records retain their stored participant ID during edits, so a
  submitted hidden field cannot move a membership to another participant.
- Project participant member counts now link to the roster administration view.
- Corrected the component media manifest to install its declared JavaScript
  assets. This fixes distribution of both the roster-role filter and the
  existing dual-list script.
- No custom CSS and no database-schema changes were introduced.

## Security boundary

- Joomla FormController and AdminController provide session-token validation.
- Create, edit and delete operations require the corresponding component ACL.
- Submitted participant, person, person-type and role combinations are checked
  against database records and the immutable profile contract.
- Date ranges, lifecycle values, field lengths and captain values are validated
  and normalized server-side.
- Unknown legacy role codes are escaped in list output.

## Verification

- The foundation suite passed with 15 profiles, 23 equivalent MariaDB and
  PostgreSQL tables, 1468 en-GB language keys, 18 menu views, 6 template
  definitions and 124 project-rule fields.
- Unit tests passed for entry contracts, project rules, template resolution and
  UUID generation.
- Integration tests passed for project participants, project-rule persistence,
  project-template inheritance and sport-type materialization.
- Playwright created, edited and deleted a temporary team participant and its
  membership through the real Joomla 6.2 administrator UI.
- Fixture cleanup left no temporary projects, people or teams in the test
  database. Application logs contained no related warning or fatal error.
- A rebuilt package update installed both JavaScript files and their public
  URLs returned the exact packaged content.
- Test backup: `/mnt/disk-b/server-backups/joomla62/20260810-201642`.

## Model boundary

`lifecycle_state` represents the membership's current state. It is not a history
of repeated injuries, suspensions or departures. Those episodes belong in a
separate availability and eligibility aggregate so a continuous membership does
not have to be duplicated.

## Review limitation

## Recommended next increment

Define the universal competition schedule foundation: project stages, rounds
and matches driven by the profile's contest and match-structure contracts.
Availability episodes should then be attached to lineup eligibility rather than
embedded in the schedule itself. Legacy migration remains deferred until the
administrator application is complete.
