# Universal runtime position CRUD

Date: 2026-08-08

## Implemented

- Added standard Joomla toolbar actions: New, Edit, Publish, Unpublish, Check-in and Delete.
- Added a two-tab Joomla position editor with Sport Type ownership, local name, stable code, person type, lineup group, optional parent and event/statistic capabilities.
- `person_type` and `lineup_group` are extensible profile-defined codes, not closed PHP enums.
- Local saves use `source=local` and never modify immutable sport-profile JSON.
- Position codes are unique per Sport Type and validated with a portable stable-code format.
- Parent positions must belong to the same Sport Type; self-parenting and hierarchy cycles are rejected.
- Deletion is blocked when the role code is assigned to a member of a project using the same Sport Type.
- Standard component ACL controls every toolbar and form action.

## Universal-system boundary

Known values such as `player`, `staff` and `official` are profile data conventions only. The runtime model accepts new person types and lineup groups required by future sports without schema or engine changes.

## Review limitation

## Deployment

Installed only on the MariaDB and PostgreSQL Joomla 6.2 development instances. No production deployment was performed.
