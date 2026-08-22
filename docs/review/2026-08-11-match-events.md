# Match events review

## Scope

- Added profile-defined match-event persistence for MariaDB/MySQL and PostgreSQL.
- Added immutable event metadata and actor-name snapshots with nullable catalog, lineup, official and score-segment provenance.
- Added match-scoped validation and native Joomla administration UI.
- Added the Match events action to the round match list.
- Extended reset, uninstall, baseline, update SQL and architecture coverage.

## Safety boundaries

- Runtime accepts only events from the immutable project profile and contains no sport-code branches.
- Participants, lineup people, officials and score segments must belong to the same match.
- System events cannot be assigned to participants or actors.
- Events requiring a second person require two different members of one participant lineup.
- Event writes never mutate canonical results, score segments or lineup changes.
- Controllers retain Joomla ACL, CSRF, translated public feedback and technical logging.

## Review

## Verification

- Foundation check: 15 profiles, 38 equivalent tables, 1847 en-GB keys.
- All 11 unit tests and PHP syntax checks passed.
- Clean package installation and Joomla Database Checker passed on fresh MariaDB and PostgreSQL databases at schema `6.2.0-2026081106`.
- `tests/Integration/match-events.php` passed on both deployed drivers, covering non-profile rejection, cross-match ownership, required second persons, system events, exact clocks, ordering and snapshots.
- The complete match Playwright workflow opened the new workspace and cleaned all fixtures.
- The full administrator layout passed at 1440 x 1000 and 390 x 844 without untranslated component constants or horizontal overflow.
- Pre-deployment backup: `/mnt/disk-b/server-backups/joomla62/20260811-202330/`.
