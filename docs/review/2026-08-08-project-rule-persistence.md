# Project rule relations and persistence - implementation review

Date: 2026-08-08

## Delivered

- Safe declarative linear constraints with `eq`, `lte` and `gte` operators.
- Relational validation of profile defaults and resolved project overrides.
- Eight additional project-overridable lineup fields, for 124 fields total.
- Profile `1.0.2` for the five profiles whose immutable payload changed.
- Default string and list-item length limits.
- Transactional `ProjectRuleConfigRepository` for read, insert, update and delete.
- Profile lookup through the locked project instead of caller-provided profile data.
- Canonical JSON, SHA-256 verification and 65,535-byte persistence limit.
- Empty-object deletion semantics restoring inheritance.
- Real Joomla integration fixture test shared by MariaDB and PostgreSQL.

## Integration scenarios

The integration test creates an isolated competition, season, sport type and project,
then verifies:

1. missing configuration reads as an empty override object;
2. first save inserts canonical JSON;
3. second save updates the existing row;
4. a relationally invalid save rolls back and preserves the previous value;
5. direct checksum tampering is rejected on read;
6. saving an empty object deletes the row;
7. all fixture records are removed in reverse dependency order.

## Issues found during dual-driver testing

### Joomla bind-by-reference

Joomla 6.2 `DatabaseQuery::bind()` requires a variable by reference. Passing an
expression or array offset fails. Repository record values and fixture UUID
expressions were moved into dedicated scalar variables before binding.

### Driver value coercion

The PostgreSQL driver may coerce a bound integer variable to a string. The fixture
originally reused that variable later as a strict integer and its cleanup failed.
Dedicated bind variables and explicit integer casts at cleanup boundaries now avoid
the mutation. The repository's typed method parameters and method-local bind values
already isolate callers from this behavior.

Both failures happened only in disposable fixtures. Selective cleanup confirmed no
fixture data remained before the successful rerun.

## Verification

The following final checks passed:

- foundation test: 15 profiles, 12 matching tables, 819 `en-GB` keys, 18 views,
  6 template definitions and 124 project-rule fields;
- template resolver and project-rule validator unit tests;
- relational negative tests for football, ice hockey, MMA/boxing, tennis and
  volleyball plus a valid coordinated tennis override;
- syntax validation for 43 administrator PHP files and the integration fixture;
- JSON parsing for every profile, XML validation and ZIP integrity validation;
- final package installation on MariaDB and PostgreSQL;
- full repository integration cycle on both `mysqli` and `pgsql` drivers;
- profile history on each database:
  - 15 superseded `1.0.0` versions;
  - 10 active and 5 superseded `1.0.1` versions;
  - 5 active `1.0.2` versions;
- zero project-rule configuration rows and zero fixture rows after cleanup;
- no warnings, errors, exceptions or fatal messages in either application log.

Package:

- `dist/com_joomleague-6.2.0-dev.zip`
- SHA-256: `3f2d9fc35fad9cf737f697d3420208eebffdff2c1bee61a67ce981a659c6b7a7`

The final artifact is installed on both Joomla 6.2 test database variants. No
production or `fotbal2` environment was changed.

## External review