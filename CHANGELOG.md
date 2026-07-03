# Changelog

All notable changes to this project will be documented in this file.

## 0.35.2 - 2026-07-02

- Added missing uninstall cleanup for the country lookup table.
- Added country indexes for club, league, person and playground records.
- Replaced raw filtering on migrated extended fields with safe HTML filtering.
- Added missing module language strings for legacy frontend configuration keys.
- Updated the internal install version marker to the current release.

## 0.35.1 - 2026-07-02

- Unified package, component, module and plugin manifest versions.
- Added validation for child extension manifest versions.
- Prepared the release for Joomla update server verification.

## 0.30.0 - 2026-07-02

- First public GitHub milestone release candidate.
- Prepared package metadata for Joomla update discovery from `0.21.50`.
- Prepared release assets for package ZIP, update XML and changelog XML.
- Confirmed the package build path for Joomla 6 source distribution.
- Made legacy schema column updates safe for databases that already contain those columns.

## 0.21.50 - 2026-07-02

- Private bridge package for validating the Joomla update server path.
- Added package update server URL to the Joomla package manifest.
- Added package changelog URL to the Joomla package manifest.
- Prepared update metadata for the later `0.30.0` release test.

## 0.20.14 - 2026-07-02

- First synchronized source tree in the public repository.
- Added Joomla 6 package build verification through GitHub Actions.
- Aligned package documentation with the package manifest version.

## 0.20.10 - 2026-07-01

- Initial Joomla 6 package repository setup.
- Package contains `com_joomleague`, Joomla site modules and integration plugins.
