# Administrator tools and data exchange

## Scope

The Joomla 6.2 administrator now provides a native Tools dashboard with canonical
JoomLeague table export, restricted SQL import, bundled sport-profile
synchronisation, template access and environment diagnostics.

## Safety boundaries

- SQL exports contain both canonical driver-specific table structure and data.
- The exporter only accepts installed `#__joomleague_*` tables selected from the
  server-generated catalogue.
- The importer accepts only `CREATE TABLE IF NOT EXISTS`, `CREATE INDEX` and
  `INSERT` statements targeting `#__joomleague_*` tables.
- `DROP`, `ALTER`, `UPDATE`, `DELETE`, physical legacy prefixes and foreign tables
  are rejected before execution.
- The complete file is validated before execution. Safe idempotent DDL runs
  first; inserted data is transactional. Driver-native no-op conflict handling
  counts duplicate rows without raising a constraint exception, while other
  data errors roll back the imported rows.
- Uploads require Joomla `core.manage`, CSRF validation, a real HTTP upload, a
  `.sql` extension and a 100 MB application limit.
- Destructive demo reset remains CLI-only behind both the environment gate and
  `--force`; the web diagnostics page only reports its state.

## Sport profiles

Manual synchronization and component installation now call the same installer
implementation. The existing immutable-version and active-project safeguards
therefore apply identically to both entry points.

## Legacy migration boundary

`/mnt/disk-a/fotbal1-joomleague-3.0.22.sql` is a read-only reference input for the
next migration-administration point. It is intentionally rejected by canonical
SQL import. No files or behavior on `migrate.klucon.cz` were changed; any future
prototype is restricted to `migrate.klucon.cz/new` after a separate decision.

## Verification

- PHP syntax validation passed for the complete administrator component.
- Architecture verification passed with 15 profiles, 48 equivalent tables,
  2,195 ordered en-GB/cs-CZ keys and 19 manifest menu views.
- Clean package installation passed on MariaDB and PostgreSQL.
- Deployed integration passed on MariaDB and PostgreSQL, including structure and
  data export, 15 duplicate-row skips, destructive-statement rejection and
  idempotent synchronization of all 15 bundled profiles.
- Playwright passed Tools, Table exports, SQL import, Diagnostics and Sport
  Profiles at 1440 x 1000 and 390 x 844 without untranslated keys, PHP output or
  horizontal overflow.
- Final test deployment backup:
  `/mnt/disk-b/server-backups/joomla62/20260815-172514`.
- No changes were made to `migrate.klucon.cz` or `fotbal2.raksice.cz`.
