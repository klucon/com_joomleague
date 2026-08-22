# Runtime sport catalogs

Date: 2026-08-08

## Implemented

- Added portable MariaDB and PostgreSQL runtime tables for positions, event types and statistics.
- Added three independent Joomla Yes/No switchers to new Sport Type initialization, enabled by default.
- Materialization uses the selected immutable profile version, validates definitions before writing and participates in the Sport Type save transaction.
- Existing Sport Type edits neither show nor process initialization options.
- Replaced the direct profile-JSON Positions catalog with a standard runtime-table list and Sport Type filtering.
- Empty catalogs now correctly explain that no working records have been created.

## Data contract

- Profile JSON is a template, not a working record store.
- Runtime rows keep profile-version provenance and a canonical source checksum.
- Event and statistic metadata retain the complete source definition for lossless future evolution.
- Position, event and statistic codes are unique only within their owning Sport Type.

## Next increment

Add normal CRUD and assignment views for the three runtime catalogs without changing immutable bundled profile payloads.

## Review limitation

## Verification

- Foundation verification passes with 15 profiles, 21 equivalent MariaDB/PostgreSQL tables and complete en-GB key coverage.
- Transactional integration tests on both database drivers selectively created 8 basketball positions, 0 event types and 8 statistics, then rolled back without residue.
- New-form switch defaults and the runtime Positions page pass browser checks.
- The complete administrator layout passes on desktop and mobile against both test database drivers.
- The package was installed only on the Joomla 6.2 development instances; no production deployment was performed.
