# ADR 0005: Profile-driven project rule validation

- Status: Accepted
- Date: 2026-08-08
- Target: JoomLeague 6.2.0

## Context

ADR 0004 stores project-specific sport rules as sparse JSON overrides. Accepting an
arbitrary subset of a profile payload would also make identity, labels, migrations,
events or statistics project-editable. Hard-coded PHP allowlists per sport would make
the engine non-universal and require a release for every new sport.

The bundled profiles still use the transitional profile schema, but project writes
need a strict contract before administrator forms or migration adapters can persist
rule overrides.

## Decision

Every immutable sport-profile version declares `project_rule_schema` with:

- independent schema version `1.0.0`;
- an explicit map of RFC 6901 JSON Pointer paths;
- scalar or list type;
- optional numeric range, string pattern or enum;
- optional list cardinality and item type.

Only declared leaf paths are project-overridable. PHP contains no sport codes or
sport-specific branches. Adding a sport or changing its configurable rules requires
a new immutable profile version, not an engine change.

The first contract covers 116 fields across all 15 bundled profiles. It deliberately
excludes structural identity, event and statistic definitions, position catalogs,
migration aliases, labels, scoring model and template defaults.

## Validation

`ProjectRuleValidator` validates both the profile schema itself and sparse override
objects. It rejects:

- malformed or unsupported schema versions;
- pointers that do not resolve to a profile default;
- undeclared override paths;
- nulls, objects or lists where another type is required;
- numeric values outside their range;
- strings outside their format or enum;
- lists with invalid cardinality or item types.

Associative objects merge recursively. Lists replace the complete inherited list.
The immutable source profile is never modified.

`CanonicalJson` recursively sorts object keys while preserving list order and emits
UTF-8 JSON without escaped slashes. SHA-256 is calculated over that canonical form,
so equivalent override objects produce the same checksum on MariaDB and PostgreSQL.

## Profile lifecycle

Adding the rule contract changes the immutable profile payload. All bundled profiles
therefore advance from `1.0.0` to `1.0.1`. Installation creates new active versions
and marks prior active versions superseded; old versions and project bindings remain
available for historical interpretation.

## Consequences

- Administrator forms and migration adapters can share one validator.
- Unknown configuration fails before any database write.
- New sports remain data-driven.
- Cross-field rules such as `sets_to_win <= maximum_sets` are not yet represented;
  a future schema increment must add declarative relational constraints before the
  corresponding write form is enabled.
- Human-facing labels for generated rule forms are deferred to that form increment.
