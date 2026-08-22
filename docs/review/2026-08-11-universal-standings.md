# Universal standings administration

## Scope

Point 2 introduces a profile-driven standings contract rather than sport-specific PHP branches. All 15 bundled profiles declare their metrics, result classification, points and deterministic sorting. The runtime calculates immutable snapshots and publishes one current snapshot per project, optional stage and scope.

The implementation includes MariaDB/MySQL and PostgreSQL schema parity, decimal-safe arithmetic, a repository, calculator, contract validation, project-context administration and Joomla-native toolbar and table markup.

## Persistence

- `#__joomleague_standing_snapshot` owns immutable calculation metadata.
- `#__joomleague_standing_snapshot_row` owns ranked rows and metric payloads.
- `#__joomleague_standing_current` atomically points consumers to the published snapshot.
- Recalculation is idempotent for an unchanged input hash and preserves snapshot history.

## Verification

- Contract validation covers all bundled profiles.
- Calculator fixtures cover football, ice hockey, volleyball, chess and running races.
- Repository integration runs against MariaDB and PostgreSQL and verifies calculation, publication, idempotency and history.
- Clean package installation and Joomla Database Checker run against both database drivers.
- Playwright verifies desktop/mobile rendering, recalculation, untranslated constants, overflow and return to the project panel.
- Architecture checks retain project-only navigation, owned tables, ACL, CSRF, logging and Joomla-native presentation.
- The final packaged build was deployed to the Joomla 6.2 development stack and passed Joomla Database Checker on MariaDB and PostgreSQL at schema anchor `6.2.0-2026081108`.
- The final pre-deployment backup is `/mnt/disk-b/server-backups/joomla62/20260811-223924/`.

## Czech administration

The component package now includes `cs-CZ` beside the canonical `en-GB` source language. Both INI files have an identical ordered key set, and architecture verification rejects missing, extra or reordered Czech keys. Format placeholders and UTF-8 encoding are checked before packaging. The deployed Czech administrator was exercised by the standings Playwright flow on desktop and mobile.

## Current boundary

Only each profile's default standings scope (`total` or `overall`) is executable. Additional home, away, group and category scopes require explicit contracts and must not be inferred from sport codes.

Development project 60 remains pinned to a superseded profile schema without a standings contract. It is intentionally not rewritten silently. Pre-release profile cleanup or explicit reassignment will be handled as a separate data operation.