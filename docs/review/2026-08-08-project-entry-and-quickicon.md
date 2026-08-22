# Project entry foundation and Quick Icon review

Date: 2026-08-08

## Delivered

- Added `entry_model` version 1.1.0 to all 15 bundled sport profiles.
- Added strict profile entry-model validation and unit coverage.
- Added equivalent MariaDB and PostgreSQL schemas for club, team, person, project entry and entry member.
- Added update `6.2.0-20260808.2` and matching uninstall order.
- Added a native Joomla `quickicon` plugin linking to `index.php?option=com_joomleague&view=dashboard`.
- Added a package installer containing the component and Quick Icon plugin; package installs enable the bundled plugin.

## Verification

- Foundation test: 15 profiles and 17 equal database tables.
- Profile entry-model test: all team/person/group contracts valid.
- MariaDB and PostgreSQL package installation successful.
- Both databases report schema version `6.2.0-20260808.2`.
- Valid entry/member writes succeeded inside transactions and were rolled back.
- Invalid team entries without a team were rejected by both drivers.
- Administrator HTML contains the enabled JoomLeague Quick Icon and the exact dashboard URL.

## Review limitation

## Deferred intentionally

- Profile-aware write repository and ACL checks.
- CRUD screens for clubs, teams, persons and project entries.
- Availability, standings adjustments, divisions, line-ups and match participants.
- Legacy migration mappings into the new entry aggregate.
