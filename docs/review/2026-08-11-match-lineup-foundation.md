# Match lineup administration foundation

## Implemented

- Canonical cross-driver `match_lineup_member` persistence.
- Historical snapshots that survive removal of their source project membership.
- Match-participant and project-entry ownership validation.
- Match-date membership eligibility in the effective match timezone.
- Immutable-profile validation of person type, role, starter limit and captain support.
- Transactional multi-selection assignment and removal.
- Joomla toolbar, participant navigation and Players, Staff and Substitutions tabs.
- A lineup shortcut in the round match list.

The Substitutions tab is present to establish the final information architecture but remains descriptive until substitution rows are implemented over stable lineup member identifiers.

## Safety

Controllers enforce Joomla CSRF and `core.edit` ACL. Expected invalid selections are not exposed as technical details; exceptions are written to the `com_joomleague` log and the administrator receives translated feedback. Removal requires the lineup ID, match ID and participant ID to match.

## Verification

- Foundation and all unit suites pass with 34 equivalent canonical tables.
- Joomla package installation and Database Checker pass at schema `6.2.0-2026081103` on MariaDB and PostgreSQL.
- `tests/Integration/match-lineup.php` passes on both drivers and removes only its named fixture records.
- Pre-deployment backup: `/mnt/disk-b/server-backups/joomla62/20260811-192848/`.
