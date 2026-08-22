# Source version inspector review

- Date: 2026-08-10
- Target: JoomLeague 6.2.0 development branch
- Deployment: Joomla 6.2 test stack only

## Delivered

- Added a pure parser for historical `#__joomleague_version` rows.
- Added a read-only database inspector that runs only for legacy or mixed
  schemas, projects allowlisted columns and reads at most 20 rows.
- Kept schema classification and reported historical version as separate
  evidence. Canonical and unknown schemas fail closed without querying a
  historical version table.
- Added source version and conservative source family to the migration
  inventory screen.
- Added English language constants, parser fixtures and ADR 0020.

## Verification

- PHP syntax checks passed for all changed PHP files.
- Foundation test passed with 15 profiles, 23 equivalent MariaDB/PostgreSQL
  tables, 1402 English language keys, 18 menu views, 6 template definitions and
  124 project-rule fields.
- Clean package installation passed on temporary MariaDB and PostgreSQL Joomla
  instances.
- Repeated package update and Joomla Database Checker passed on both persistent
  Joomla 6.2 test instances.
- Authenticated browser verification passed for the canonical migration
  inventory. No untranslated component constants were present.
- Recent application logs contained no PHP warnings, deprecations or fatal
  errors related to the deployment.
- Test backup: `/mnt/disk-b/server-backups/joomla62/20260810-191704`.

## Review limitation

## Boundaries

The implementation does not modify source data, create migration batches or
claim that a reported version proves the database's original historical
release. No production or `fotbal2.raksice.cz` deployment was performed.

## Recommended next increment

Introduce a read-only external source connection/file inventory with a stable
source fingerprint. It should validate connection metadata and entity counts,
retain provenance, redact credentials, and still perform no canonical writes.
