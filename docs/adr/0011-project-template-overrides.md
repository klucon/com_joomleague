# ADR 0011: Sparse project template overrides

- Status: Accepted
- Date: 2026-08-08

## Context

Frontend template settings already resolve through five ordered layers: registry defaults, immutable sport-profile defaults, local profile overrides, project overrides and presentation overrides. The administration previously exposed the profile layer but had no safe editor for the project layer.

## Decision

Each project exposes only template definitions listed in the `template_defaults` object of its bound immutable sport-profile version. The editor is generated from the central template registry and shows four pieces of information for every setting: inherited value, explicit override switch, project value and currently effective value.

Unchecked settings are omitted. A template with no enabled settings has its sparse database row deleted and therefore inherits completely. The browser never submits template or field identifiers directly: deterministic 96-bit SHA-256 prefixes are mapped back to the authoritative registry on the server.

All templates submitted for one project are validated before writing and persisted inside one database transaction. The repository locks the project row, reloads its authoritative profile, rejects unsupported templates and fields, writes canonical JSON with a SHA-256 checksum, and supports both MariaDB and PostgreSQL through Joomla Database APIs.

The controller requires `core.edit` and a valid Joomla CSRF token. The interface uses Joomla toolbar, form controls, tables and utility classes without custom CSS or JavaScript.

## Consequences

- Project presentation can differ without copying profile defaults.
- Newly added registry fields become available without project-specific PHP forms.
- A failed value in any template rolls back the complete multi-template operation.
- Existing five-layer runtime resolution remains the single source of effective values.
- Template definitions remain intentionally small and typed; richer fields require registry metadata before they can be edited.

## Verification

- Registry, resolver, profile and architecture tests pass locally.
- Integration coverage verifies atomic insert, rejected-write rollback and inheritance restoration.
- MariaDB and PostgreSQL integration tests passed after package installation.
- The authenticated administration view returned HTTP 200 and rendered four football template groups with 38 dynamic control rows on both Joomla 6.2 database drivers.
