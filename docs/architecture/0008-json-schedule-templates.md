# ADR 0008: JSON-driven schedule templates

## Decision

Schedule structures are versioned JSON resources. Runtime code selects a template by contest type and participant count; it does not branch on a sport code.

Bundled executable templates cover Berger round-robin tables for 2-30 entries, mirrored return legs, and repeatable mass-start races. Single elimination, double elimination, group/playoff and Swiss JSON contracts are catalogued but remain disabled until their progressive participant resolvers are implemented.

The planner always produces a read-only preview before writing. Apply stores a checksum audit record and creates rounds, matches and canonical match participants in one transaction. Reapplying identical inputs is idempotent. Participant and venue overlaps are blocking unless the administrator explicitly permits them.

## Consequences

- New draw types can be added without changing sport-specific PHP.
- MariaDB/MySQL and PostgreSQL store identical generation audit data.
- JSON template integrity is tested independently of Joomla and the database.
- Progressive formats must resolve winners, rankings or seeds through dedicated generic resolvers before becoming executable.
