# Match officials review

## Scope

- Added generic project actor-role registration for profile-defined officials.
- Added historical match-role snapshots with nullable project provenance.
- Supported both canonical persons and teams as role actors.
- Added project-panel and match-list navigation plus native Joomla administration forms.
- Extended reset, uninstall, baseline and update SQL for both supported database drivers.

## Safety boundaries

- Roles originate from the immutable project profile; no sport code appears in runtime logic.
- Project ownership and match-date availability are checked before assignment.
- Overlapping project assignments for one actor and role are rejected.
- Match snapshots survive removal from the current project pool.
- Controllers retain Joomla ACL, CSRF, translated public feedback and technical logging.

## Review status

## Verification

- Foundation check passes with 15 profiles, 37 equivalent tables and 1811 en-GB keys.
- All unit tests and PHP syntax checks pass.
- `tests/Integration/match-officials.php` passes on MariaDB and PostgreSQL, including person/team actors, interval overlap rejection, match-date eligibility, foreign-project rejection and snapshot retention.
- The complete match Playwright workflow opens both new administration views and removes its match, round and stage fixtures afterward.
- The full administrator layout passes at 1440 x 1000 and 390 x 844 without untranslated component constants or horizontal overflow.
- Joomla Database Checker reports schema `6.2.0-2026081105` current on both development drivers.
- Pre-deployment backup: `/mnt/disk-b/server-backups/joomla62/20260811-195657/`.
