# ADR 0009: Local sport types derived from immutable profiles

- Status: Accepted
- Date: 2026-08-08

## Context

Bundled sport profiles are versioned, read-only definitions. Projects cannot use a profile directly because installations need local sport identities, names and publication control while retaining a precise rules contract.

## Decision

Sport types use standard Joomla CRUD. A sport type stores a local name, stable code, alias and a required reference to one active version of a published sport profile. The profile selector is populated by the model from database records and uses translated profile labels; no driver-specific concatenation query, custom field class, JavaScript or CSS is required.

Profile JSON and local override JSON are not editable in this basic form. Profile versions remain immutable. Once a project references a sport type, changing its profile-version binding is rejected by the model and protected by the database composite foreign key. Deletion is rejected while projects reference the sport type.

## Consequences

- Multiple local sport types may derive from the same immutable profile.
- Projects can select only initialized local sport types.
- Existing projects cannot silently move to another rules version.
- Profile upgrades require an explicit future migration workflow rather than editing a used binding.
- Validated local rule overrides can later be exposed separately without weakening the base profile contract.

## Verification

- Standard Joomla list, filters, pagination, toolbar and edit form.
- PHP, XML and language architecture checks.
- Authenticated create and delete through Joomla controllers on MariaDB and PostgreSQL.
- No temporary sport-type records remain after verification.
