# ADR 0003: Layered template configuration

## Status

Accepted for the JoomLeague 6.2 foundation.

## Context

Frontend presentation differs by sport and by project. Treating every template setting as a component-wide option would make one installation unable to represent football, races and set-based sports correctly. Copying complete template parameter sets into every project would make profile updates and migrations ambiguous.

## Decision

Template parameters are resolved in this order:

1. versioned template-definition defaults;
2. immutable bundled sport-profile defaults;
3. sparse local overrides for a specific sport-profile version;
4. sparse project overrides;
5. menu or module presentation overrides.

Later layers take precedence. Lists are replaced as complete values. Only associative objects may be merged recursively. Every persisted key and value is validated against the versioned template definition.

Joomla `com_config` contains installation-wide operational options only. It does not contain sport rules or frontend template parameters.

`#__joomleague_profile_template_config` stores only local differences and references an immutable sport-profile version. Project override persistence is intentionally deferred until the canonical project table exists; the resolver already accepts that layer so consumers will not need an API change.

## Consequences

- A profile update cannot silently reinterpret an override created for another profile version.
- Projects inherit future compatible profile defaults without duplicated rows.
- Unsupported settings fail validation instead of being ignored.
- MariaDB/MySQL and PostgreSQL keep equivalent persistence contracts.
- Legacy monolithic template rows require an explicit migration adapter into sparse profile and project layers.
