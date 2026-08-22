# JoomLeague 6.2 template and component configuration

## Implemented

- Joomla `com_config` now contains installation-wide general, dashboard, notification, import and media settings.
- Every configurable field has an English label and description.
- Six versioned template definitions cover project, results, ranking, match report, race results and participant views.
- The Templates administration view shows bundled profile values, sparse local overrides and effective values per immutable sport-profile version.
- The template resolver supports the complete precedence chain: definition, profile, local profile override, project override and menu/module presentation override.
- Unknown fields and invalid types are rejected.
- MariaDB/MySQL and PostgreSQL include equivalent profile-template override tables and update scripts.
- Project persistence is intentionally deferred until the canonical project table can provide a real foreign key.
- A newly installed sport-profile version supersedes the previous active version.

## Verification

- 15 sport profiles validate against 6 template definitions.
- 7 foundation tables are equivalent across both database drivers.
- 795 bundled `en-GB` language constants pass the architecture check.
- PHP lint, XML validation, resolver tests and ZIP integrity checks pass.
- The package installs successfully on both Joomla 6.2 test variants.
- MariaDB authenticated HTTP checks pass for Dashboard, Templates and `com_config`.
- PostgreSQL schema and installation checks pass; an authenticated browser check was not available because its administrator password is not recorded with the separate PostgreSQL test instance.

## Next decision

Define the canonical `project` aggregate and its foreign keys. Once accepted, add sparse project-template overrides without changing the resolver API.
