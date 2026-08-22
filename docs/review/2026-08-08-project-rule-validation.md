# Project rule validation - implementation review

Date: 2026-08-08

## Delivered

- Explicit, versioned project-rule schemas in all 15 bundled sport profiles.
- 116 allowlisted fields spanning timed periods, set sports, points tables, race
  features, decimal chess points and list-valued configurations.
- Generic JSON Pointer validator without sport-specific PHP branches.
- Type, enum, regular-expression, range, list size and list item validation.
- Sparse recursive resolution without mutation of the profile payload.
- Canonical JSON encoding and stable SHA-256 checksums.
- Installer validation before profile synchronization.
- Immutable profile version advance from `1.0.0` to `1.0.1`.
- Sport Profiles overview count and correct active/superseded status rendering.

## Tests

- Every bundled profile validates its own schema and defaults.
- Valid football overrides preserve non-overridden nested defaults.
- Unknown fields, wrong scalar types, invalid ranges, invalid time strings, wrong
  list lengths and wrong list item types are rejected.
- Equivalent objects with different key order produce identical JSON checksums.
- Foundation checks require every profile to carry a valid rule schema.

## Deliberately deferred

- Persisting overrides through an administrator form or migration adapter.
- Cross-field relational constraints.
- Labels, descriptions and Joomla form generation for the 116 fields.
- Sport-type-level rule overrides between profile and project layers.

No write action should be enabled until cross-field constraints and the persistence
service are implemented and tested transactionally on both database drivers.

## Deployment record

The following checks passed:

- foundation architecture test: 15 profiles, 12 matching database tables, 819
  `en-GB` keys, 18 views, 6 template definitions and 116 rule fields;
- template resolver unit test;
- project-rule validator unit and negative tests;
- PHP syntax validation for all 42 administrator PHP files;
- JSON parsing for every bundled profile;
- manifest and component configuration XML validation;
- package ZIP integrity validation;
- Joomla extension upgrade on MariaDB and PostgreSQL;
- 15 prior `1.0.0` versions retained as `superseded` on each database;
- 15 new `1.0.1` versions stored as `active` on each database;
- all 30 new payload checks across both databases expose rule schema `1.0.0`;
- authenticated Sport Profiles runtime requests returned HTTP 200 on both drivers;
- both responses rendered 15 active `1.0.1` rows, project-rule counts and the
  superseded history without PHP warnings or untranslated component constants;
- temporary runtime-check administrator accounts were removed.

Package:

- `dist/com_joomleague-6.2.0-dev.zip`
- SHA-256: `9f47f949c4e85d27b99e2fe80cd9b2ffb1e95eabfb78df4fbb5453c46e7f270e`

Review URL:

- <https://joomla62.klucon.cz/administrator/index.php?option=com_joomleague&view=sportprofiles>

The PostgreSQL runtime was verified through its loopback endpoint on port `18372`.
No production or `fotbal2` environment was changed.

## External review