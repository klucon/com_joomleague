# ADR 0006: Relational project rules and transactional persistence

- Status: Accepted
- Date: 2026-08-08
- Target: JoomLeague 6.2.0

## Context

ADR 0005 validates individual project-rule values, but valid values can still form an
invalid configuration. Examples include a minimum lineup larger than the fielded
team, an ice lineup whose roles do not add up to its total, or a best-of contest whose
maximum set count does not correspond to the sets required to win.

The administrator and migration code also need one persistence boundary. Allowing
controllers to encode JSON, calculate checksums or choose a profile payload would
duplicate security-critical behavior and permit validation against the wrong sport.

## Decision

### Declarative relational constraints

`project_rule_schema.constraints` is an optional list. Each constraint has a stable
code, comparison operator (`eq`, `lte`, `gte`) and two numeric linear expressions.
An expression consists of at most ten allowlisted numeric field terms with optional
factors and a numeric constant.

The validator interprets this data directly. It does not evaluate source code and
does not branch on a sport code. Both profile defaults and effective project values
must satisfy every relation.

The first relations cover:

- football minimum lineup versus players on the field;
- ice-hockey skater and goalkeeper composition plus minimum roster size;
- regular versus championship combat-sport rounds;
- tennis and volleyball `maximum_sets = 2 * sets_to_win - 1`;
- volleyball minimum lineup versus players on court.

Changing these immutable payloads creates profile version `1.0.2` for football, ice
hockey, MMA/boxing, tennis and volleyball. Other profiles remain at `1.0.1`.

### Persistence boundary

`ProjectRuleConfigRepository` is the only accepted persistence service for project
rule overrides. It:

1. validates positive project and non-negative actor identities;
2. starts a transaction;
3. locks the project row with a portable no-op update;
4. loads the immutable payload through the project's own `profile_version_id`;
5. validates fields and relational constraints;
6. canonicalizes JSON and calculates SHA-256;
7. inserts or updates the single owned configuration row;
8. commits, or rolls back every failure.

An empty override object deletes the sparse row and restores full inheritance.
Reads verify schema version, checksum and current validity before returning data.
Payloads are capped at 65,535 bytes, strings at 255 bytes by default and list strings
at 191 bytes by default.

## Security boundaries

- Callers cannot supply a profile payload to the repository.
- SQL values are bound; table and column names are static and quoted.
- The project lock serializes writes for one project on both supported drivers.
- Checksum mismatch is a hard read failure, not an informational warning.
- The repository does not perform Joomla ACL checks. Controllers and migration jobs
  must authorize the operation before calling it and must pass the authenticated or
  system actor ID.

## Consequences

- Admin and migration paths share exactly the same validation and persistence.
- Relational rules remain profile data and can evolve without PHP sport branches.
- A generated form can safely preview an effective configuration before saving.
- Nonlinear or conditional rules beyond linear comparisons will require a future,
  explicitly reviewed operator rather than arbitrary expressions.
