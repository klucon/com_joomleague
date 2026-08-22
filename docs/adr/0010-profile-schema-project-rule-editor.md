# ADR 0010: Profile-schema project rule editor

- Status: Accepted
- Date: 2026-08-08

## Context

Sport profiles define defaults and an explicit `project_rule_schema`. Projects need sparse exceptions without copying an entire profile, accepting raw JSON or adding sport-specific columns and PHP branches.

## Decision

The Project Rules editor is generated from the immutable profile version bound to the project. Only JSON Pointer paths declared in `project_rule_schema.fields` are rendered or accepted. Core Joomla fields are selected from schema types:

- boolean: radio switcher;
- integer and number: bounded numeric input;
- enum: select list;
- string: text input;
- array: comma-separated typed values.

Every rule has a separate Override checkbox. Unchecked fields are omitted from the sparse object and inherit the profile default. Saving an entirely empty object deletes the configuration row and restores complete inheritance.

The controller requires Joomla `core.edit` and a valid CSRF token. It sends the sparse object only to `ProjectRuleConfigRepository`, which reloads the authoritative project profile, validates types, ranges, permitted paths and relational constraints, writes canonical JSON and its checksum inside a transaction. No raw JSON editor, custom CSS or custom JavaScript is used.

Form field names are derived from a 96-bit prefix of the pointer SHA-256 digest; the submitted names never become JSON paths. The server reconstructs the allowed name-to-pointer mapping from the immutable schema on every request. Enum definitions are validated for scalar type consistency before dynamic XML fields are built.

## Localization

Profiles currently do not carry field-specific translation metadata. The first editor version presents a readable hierarchy derived from each schema pointer inside a translated generic label/description frame. Adding optional `label_key` and `description_key` metadata is reserved for the profile schema stabilization pass; persistence and validation do not depend on display labels.

## Consequences

- New sports and rule fields appear without new PHP forms.
- Profile defaults remain visible beside project overrides.
- Unsetting an override automatically follows future resolution rules while retaining the project's immutable profile version.
- Invalid or hidden submitted fields are ignored because only schema-derived hashes are processed.
- Arrays have a deliberately conservative administration representation and may later receive a core subform when profile metadata needs richer item editing.

## Verification

- Football numeric, boolean and numeric-array overrides saved through Joomla controllers.
- Sparse canonical JSON verified on MariaDB and PostgreSQL.
- Unchecking all overrides deleted the sparse configuration on both drivers.
- Existing profile schema, constraint, checksum and repository tests remain authoritative.
