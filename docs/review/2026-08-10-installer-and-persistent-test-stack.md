# Installer and persistent Joomla 6.2 test stack

## Scope

This change closes the update and test-infrastructure defects discovered after
updating the Joomla 6.2 development checkout on 2026-08-10.

## JoomLeague installer

- MariaDB and PostgreSQL migrations tolerate structures already repaired by
  Joomla Database Fix using Joomla's `/** CAN FAIL **/` marker only on repeatable
  column, constraint and index creation statements.
- `6.2.0-2026080808.sql` is the canonical schema anchor for both drivers. It
  sorts after the historical same-day migration names for both Joomla's
  installer and Database Checker.
- The component installer removes known obsolete `6.2.0.sql` and
  `6.2.0-20260801.sql` files from both driver directories.
- Fresh installations explicitly load the two profile-validation services from
  the installed component before profile synchronization. The installer no
  longer depends on Joomla rebuilding its component namespace map first.
- The architecture test rejects a missing schema anchor and
  `ADD COLUMN IF NOT EXISTS`, which Joomla Database Checker misparses.

## Test stack

- MariaDB and PostgreSQL use separate persistent Joomla webroots.
- The image stores canonical Joomla core files in `/opt/joomla` and initializes
  only an empty `/var/www/html`.
- `configuration.php`, installed extensions and media survive container
  recreation.
- Joomla CLI always runs as `www-data`; writable directories are checked after
  each deploy.
- PHP, Composer, MariaDB and PostgreSQL base images are pinned by digest.
- Backup artifacts are stored on disk-b with private permissions and SHA-256
  checksums.

## Automated verification

The deploy pipeline now performs:

1. A complete webroot and database backup.
2. Component architecture validation and package build.
3. Joomla image build and application-container recreation.
4. Joomla core synchronization while preserving site configuration and runtime
   directories.
5. Two consecutive full JoomLeague package updates on each database driver.
6. Joomla Database Checker, scheduler-task uniqueness, PHP-limit, ownership,
   obsolete-file and HTTP smoke checks.

An additional clean-install test creates temporary MariaDB and PostgreSQL
databases and temporary Joomla webroot volumes, installs Joomla and the complete
JoomLeague package from scratch, then removes only those temporary resources.
Every Joomla CLI command is checked together with `stderr`; PHP warnings, fatal
errors and Joomla `[ERROR]` output fail the test even if a later line contains
the expected success text.

The restore test extracts both webroots and restores both database dumps into
temporary databases. It removes the temporary databases after validation.

## Verified result

- Repeated full package update: passed on MariaDB 11.4 and PostgreSQL 18.
- Fresh Joomla and JoomLeague installation: passed on both database drivers.
- Schema version: `6.2.0-2026080808` on both drivers.
- Database structures: up to date on both drivers.
- Telemetry scheduler tasks: exactly one per installation.
- Persistent configuration hashes: unchanged across repeated container
  recreation.
- Backup restore test: passed for both webroots and both databases.
- Latest verified backup: `/mnt/disk-b/server-backups/joomla62/20260810-182531`.
- Public endpoint: HTTP 200 without PHP warnings or fatal errors.