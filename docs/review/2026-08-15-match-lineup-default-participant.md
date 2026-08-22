# Match lineup default participant

The match-lineup administrator route now works with `match_id` alone. When no
valid `participant_id` is supplied, it selects the first match participant with
published project-entry members and falls back to the first participant in slot
order. Participant controls display roster counts and still allow explicit
home/away switching.

The migrated JL3 fixture confirms that match 3441 selects FC Rakšice A and
loads 23 players plus 5 staff members. FC Slovan Rosice B has no source roster.

Verification completed on MariaDB and PostgreSQL:

- architecture suite and PHP syntax
- match-lineup repository integration test
- desktop and mobile Playwright test against the route without `participant_id`
- pre-deployment backup: `/mnt/disk-b/server-backups/joomla62/20260815-210800/`
