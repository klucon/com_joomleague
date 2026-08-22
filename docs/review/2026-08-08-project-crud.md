# Project CRUD implementation review

## Scope

Implemented the first complete project administration workflow for the Joomla 6.2 development component:

- standard Joomla project list, filters, pagination and state actions;
- project create/edit form with a description for every configurable field;
- competition, season, sport type, project type and lifecycle filters;
- server-owned profile-version binding;
- profile-driven project-type and default-start-time handling;
- Joomla-native timezone inheritance, with an optional project override;
- equivalent runtime checks on MariaDB and PostgreSQL.

No schema change, custom CSS or custom JavaScript was required.

## Security and integrity

- `profile_version_id` is not exposed by the form and is overwritten from the selected sport type during every save.
- Project type is checked against the immutable profile payload on the server.
- Joomla ACL gates create, edit, state and delete operations.
- Form controllers and templates use Joomla CSRF tokens.
- UUID, timezone, time, lifecycle, round mode, date order and non-negative delay are validated before persistence.
- SQL values are bound; filter ordering is restricted by `ListModel::filter_fields`.

## Automated checks

- Foundation: 15 profiles, 12 equivalent tables, 962 en-GB keys, 18 menu views, 6 template definitions and 124 project-rule fields.
- Template resolver: all five layers and six definitions pass.
- Project-rule validator: all 15 profiles pass.
- UUID factory: 1,000 unique RFC 4122 version 4 identifiers.
- PHP syntax and project/filter XML parse successfully.

## Runtime checks

Both `joomla62-dev-app` (MariaDB 11.4) and `joomla62-dev-postgresql-app` (PostgreSQL 18) passed:

1. authenticated project list and new-project form render with HTTP 200;
2. project creation through the Joomla controller;
3. server-derived profile version and football default start time `17:00`;
4. rejection of unsupported project type `race` for football;
5. cleanup of all temporary project, sport type, season and competition records.

Review URL: <https://joomla62.klucon.cz/administrator/index.php?option=com_joomleague&view=projects>

## External review status

## Next boundary

The next coherent administrator slice is sport-type creation from bundled profiles, because projects intentionally require a configured sport type. After that, project rule overrides can be exposed using the existing schema validator and repository without adding sport-specific columns to the project form.
