# Stage progression topology review

## Delivered

- Canonical `stage_transition` persistence for MariaDB/MySQL and PostgreSQL.
- Standard Joomla list/edit MVC reached from the Stages toolbar.
- Selectors for all participants, standings rank ranges, match outcomes and manual qualification.
- Native controls for ranks, standings scope, match outcome, source round, carry-over declaration and target seeding; internal JSON is hidden.
- Project ownership, unique code, source-round ownership, distinct endpoint and directed-cycle validation.
- Reset, uninstall, update-chain and fresh-install integration.

## Verification

- Backup: `/mnt/disk-b/server-backups/joomla62/20260815-160149`.
- Architecture: 15 profiles, 44 equal database tables and 2010 equal `en-GB`/`cs-CZ` keys.
- Unit validation covers normalized selector contracts and graph cycles.
- Integration stores a valid edge and rejects its cyclic reverse on both `mysqli` and `pgsql`.
- Fresh package installation and Joomla Database Checker pass on temporary MariaDB and PostgreSQL databases at `6.2.0-2026081502`.
- Playwright verifies the stages entry point, list and edit form without diagnostics, untranslated keys, exposed JSON or horizontal overflow.

## Remaining within point 1

Implement an explicit, idempotent and audited progression run. It must preview the resolved participants, preserve manual target assignments, record provenance and make carry-over semantics executable before point 1 can be marked complete.
