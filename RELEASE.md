# JoomLeague Release Process

This document describes the internal release workflow for JoomLeague packages,
Joomla update metadata and downloadable language packs.

## Release Inputs

Before publishing a new build, update these files:

- `pkg_joomleague.xml`
- `administrator/components/com_joomleague/joomleague.xml`
- child extension manifests under `modules/` and `plugins/`
- `CHANGELOG.md`

All extension manifests must use the same version. `CHANGELOG.md` must contain
a dated section for the released version.

## Build And Publish

Run the complete local release pipeline from the repository root:

```bash
python3 build/release_distribution.py
```

The wrapper runs these steps in order:

```text
build/validate_versions.py
build/language_packages.py
build/package.py
build/release_metadata.py
build/validate_package.py
build/update_distribution.py
```

The command fails on the first broken step.

## Generated Local Artifacts

The build writes the package and metadata under `dist/`:

- `dist/pkg_joomleague-<version>.zip`
- `dist/joomleague-update.xml`
- `dist/joomleague-changelog.xml`
- `dist/joomleague-changelog-<version>.xml`
- `dist/languages/joomleague-language-<tag>.zip`

The main package contains only the source language. Additional translations are
published as separate language packages.

## Published Download Structure

The public static download tree is generated under:

```text
/mnt/disk-a/docker/webserver/update.klucon.cz/public/joomleague
```

Public URLs:

- `https://download.klucon.cz/joomleague/`
- `https://download.klucon.cz/joomleague/releases/`
- `https://download.klucon.cz/joomleague/releases/<version>/`
- `https://download.klucon.cz/joomleague/languages/`
- `https://download.klucon.cz/joomleague/dev/`

Machine-readable URLs:

- `https://update.klucon.cz/joomleague/update.xml`
- `https://update.klucon.cz/joomleague/changelog.xml`
- `https://update.klucon.cz/joomleague/languages/manifest.json`

The generated release detail includes:

- package ZIP
- changelog XML
- update XML snapshot
- `release.json`
- `checksums.txt`
- language ZIP files

## Time Format

JSON files keep timestamps in UTC, for example:

```text
2026-07-17T12:36:46+00:00
```

HTML pages display the same timestamps in `Europe/Prague`, for example:

```text
2026-07-17 14:36 CEST
```

## Validation

`build/release_distribution.py` performs HTTP checks after publishing and
expects `200 OK` from:

- `https://download.klucon.cz/`
- `https://download.klucon.cz/joomleague/`
- `https://download.klucon.cz/joomleague/releases/`
- `https://download.klucon.cz/joomleague/languages/`
- `https://download.klucon.cz/joomleague/dev/`
- `https://update.klucon.cz/joomleague/update.xml`
- `https://update.klucon.cz/joomleague/languages/manifest.json`
- `https://download.klucon.cz/joomleague/releases/<version>/`

## Joomla Update Check

After publishing, verify that Joomla can read:

```text
https://update.klucon.cz/joomleague/update.xml
```

The update XML must reference the package ZIP on `update.klucon.cz` and include
the SHA-256 hash for the generated package.

## Language Packages

Language packages are generated from the local Weblate VCS checkout by:

```bash
python3 build/language_packages.py
```

Administrators install or update them from:

```text
administrator/index.php?option=com_joomleague&view=languages
```

The administrator screen reads:

```text
https://update.klucon.cz/joomleague/languages/manifest.json
```

It displays package version, update time, size and SHA-256 for each available
language.

## Phoca Download

JoomLeague release packages and language packages are no longer published
through Phoca Download. The static download system on `download.klucon.cz` and
`update.klucon.cz` is the source of truth.
