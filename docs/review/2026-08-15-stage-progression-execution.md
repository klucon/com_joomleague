# Stage progression execution review

## Scope

Administrator roadmap point 1 is complete. A published transition can now be previewed and applied without exposing internal JSON. The implementation remains profile-driven and does not branch on a sport code.

## Canonical behavior

- `all_entries`, `standing_rank_range` and `match_outcome` resolve participants from canonical project data.
- `manual` remains deliberately non-executable and directs administrators to target-stage assignments.
- Every distinct input has a SHA-256 checksum and one immutable `stage_transition_run` audit record.
- Repeating an unchanged input is idempotent and synchronizes assignments using the existing run.
- Manual and automatic target assignments coexist. Automatic recalculation cannot delete a manual assignment.
- An empty result removes only obsolete assignments owned by that transition.
- `all_results` includes source-stage matches involving a qualified participant.
- `mutual_results` includes only source-stage matches whose participants all qualified.
- Carried matches feed the same universal standings calculator as target-stage matches.

## Database parity

Update anchor `6.2.0-2026081503` adds `stage_entry.manual_assignment`, immutable transition runs and current transition assignments. MariaDB/MySQL and PostgreSQL baseline and update schemas expose the same 46 canonical tables.

## Verification

- Architecture verification: 15 profiles, 46 tables and matching en-GB/cs-CZ keys.
- Unit suite: all profile, result, standings, transition, template and UUID tests pass.
- Integration suite on `mysqli` and `pgsql`: preview, idempotency, audit count, manual-assignment preservation and all-results carry-over pass.
- Joomla Database Checker reports JoomLeague schema `6.2.0-2026081503` current on both drivers.
- Clean package installation passes on temporary MariaDB and PostgreSQL databases.
- Administrator transition, assignment and standings pages pass browser verification without untranslated keys or desktop/mobile overflow.
- Pre-deployment backups: `/mnt/disk-b/server-backups/joomla62/20260815-162126` and `/mnt/disk-b/server-backups/joomla62/20260815-162155`.
