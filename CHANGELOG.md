# Changelog

All notable changes to this project will be documented in this file.

## 6.1.0-alpha-60 - 2026-07-06

### Added

- **Anonymous, opt-in usage telemetry.** After installing the package a consent prompt is shown that lists the exact data that would be sent — the JoomLeague, Joomla and PHP version, the site language and a random installation identifier — before anything leaves the site. The prompt offers three choices: send once, send once a month automatically, or never. Nothing is transmitted without an explicit choice.
- **Strict privacy boundaries for telemetry.** No domain, URL, IP address, site name or personal data is ever collected. Data is sent server-side (not from the browser) to a public, transparent statistics endpoint, and the monthly option uses a lightweight heartbeat driven by the administrator control panel.
- **Telemetry option in the component configuration** so the choice can be changed or withdrawn at any time.
- **Team statistics — visual charts.** A results overview bar (wins / draws / losses) and a goals chart (home and away, scored and conceded), rendered as self-contained, dependency-free graphics that replace the legacy Flash chart.
- **Next match — recent form and preview.** The recent form of both teams (their last five results with win / draw / loss badges, opponent and score) and a match preview / annotation block.
- **Club — logo and map links.** The club logo in the header and privacy-friendly "Show on map" links for the club and each of its venues, replacing the legacy embedded-map plugin dependency.

### Changed

- The post-installation consent card is a self-contained light card that stays readable on dark administrator themes, reports the real delivery outcome, and offers a "Continue to JoomLeague" button.

### Notes

- This completes the Joomla 6 frontend rewrite: the team statistics, next match and club views now reach feature parity with the classic component while dropping legacy Flash and third-party map dependencies.

## 6.1.0-alpha-50 - 2026-07-05

- Reworked menu-item creation to select targets from lists instead of typing raw IDs.
- Added dropdown pickers for lookup targets such as project, club, team, round, playground and division.
- Added searchable modal pickers for choosing people and matches in menu items.
- Added graceful "not found" handling for menu items that point to missing records.
- Added the missing site menu-item type language constants for the administrator menu editor.
- Encoded the Powered by JoomLeague credit link in the source.
- Rewrote the project README in English with badges, a feature overview and a roadmap.

## 0.40.4 - 2026-07-05

- Replaced the prediction foreign key update SQL with Joomla installer compatible statements.
- Added frontend result matrix parity for multiple matches, cancelled matches, ruling decisions and division groups.
- Added grouped match lists for team and schedule pages.
- Added schedule display modes by round and by date, plus home/away filtering for team schedules.
- Added player match history, photo/contact details and match participation data to person profiles.
- Added a source-tree checklist for the latest frontend changes in `docs/com_joomleague_v6-checklist-2026-07-05.md`.

## 0.40.3 - 2026-07-04

- Made prediction game foreign key update SQL idempotent for repeated upgrade attempts.
- Moved guarded prediction game foreign key creation into a dedicated update step.

## 0.40.2 - 2026-07-04

- Fixed prediction ranking recalculation on the frontend by binding scalar variables instead of expressions.
- Applied the same prediction recalculation fix to the administrator recalculation action.

## 0.40.1 - 2026-07-04

- Added the public Powered by JoomLeague link to frontend component pages.
- Kept the iCalendar feed output clean without additional HTML footer markup.

## 0.40.0 - 2026-07-03

- Added the prediction game milestone with administrator management for games, tips, score recalculation and rankings.
- Added prediction game database tables to fresh installs and update SQL, including uninstall cleanup.
- Added German (`de-DE`) language files for the package, component, modules and plugins.
- Registered German language files in package, component, module and plugin manifests.
- Updated language documentation for the three supported locales.

## 0.35.4 - 2026-07-03

- Added expanded Joomla 6 frontend views for team, club, schedule and match detail pages.
- Added result matrix and combined results/standings frontend views.
- Added event ranking, statistics ranking and team statistics frontend views.
- Added rivals overview with head-to-head team balance.
- Added iCal calendar feed plus calendar subscription actions for Google, Apple, Outlook.com and Office 365.
- Updated frontend rewrite documentation and GitHub wiki source files.

## 0.35.3 - 2026-07-03

- Fixed undefined module language constants in XML configuration forms.
- Added shared name-format language aliases used by multiple modules.
- Aligned logo option language aliases with module XML values.

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
