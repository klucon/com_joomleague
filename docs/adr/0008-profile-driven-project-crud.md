# ADR 0008: Profile-driven project CRUD

- Status: Accepted
- Date: 2026-08-08

## Context

A project is a concrete competition edition connecting a competition, season and configured sport type. The canonical schema stores the exact sport-profile version so historical projects cannot silently change when a bundled profile is upgraded. The administrator UI must remain sport-neutral and must not expose JSON or allow a client request to choose an arbitrary profile-version identifier.

## Decision

Projects use standard Joomla `AdminModel`, `ListModel`, `FormController`, table, toolbar, search tools and form fields. No project-specific CSS or JavaScript is introduced.

The administrator selects a sport type and project format. On every save the server loads the selected sport type, derives its bound immutable `profile_version_id`, decodes that version's payload and accepts only a format listed in `project_types`. A submitted profile-version identifier is ignored. When no default start time is supplied, the sport profile's `match_structure.default_start_time` is inherited. An empty project timezone is persisted as `NULL` and means that Joomla's system timezone is inherited; a concrete value is stored only as an explicit project override.

The base project form contains only sport-neutral identity, schedule, round-detection, lifecycle and presentation fields. Sport-specific rule changes remain in `project_rule_config`; project template changes remain in `project_template_config`. They are not flattened into the project row or this form.

Project dates are optional boundaries of the concrete edition, unlike reusable season records. Lifecycle (`draft`, `active`, `completed`, `archived`) is independent from Joomla publication state. Round detection supports manual selection and date/match based strategies without embedding sport rules.

## Consequences

- Existing projects retain their exact profile-version relationship.
- Profile formats control which project types can be created without PHP branches by sport code.
- A sport type must be initialized from a readable profile before it can be assigned.
- Future runtime data may add a policy that prevents changing the sport type after matches exist.
- Dynamic filtering of the project-type selector can be added later only if standard Joomla field behavior is insufficient and a custom script is explicitly approved; server validation remains authoritative.

## Verification

- PHP syntax, XML and language-key architecture checks.
- Authenticated Joomla create/list flow on MariaDB 11.4 and PostgreSQL 18.
- Derived profile version and profile default start time checked in both databases.
- Unsupported `race` format rejected for the football profile on both drivers.
- Temporary integration records removed after the checks.
