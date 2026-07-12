# JoomLeague 6.1.0-alpha-150 Release Notes

JoomLeague 6.1.0-alpha-150 is a Joomla 6 alpha release focused on public packaging, multilingual SEF routing, menu-item stability and frontend publishing polish.

This release is intended for evaluation, migration testing and demo deployments on Joomla 6.1+ with PHP 8.3+. It is not a final stable release.

## Highlights

- Multilingual SEF URL support for Czech, English and German route segments.
- Canonical links for public component pages, including translated route aliases.
- Project-aware URLs for standings, results, schedules, teams, rosters, rivals, team statistics and match reports.
- Clean schedule URLs for display mode and home/away filters, for example `/rozpis/podle-data` and `/rozpis/tym/{team}/doma`.
- Translated standings scope URLs, for example `/tabulka/rozsah/doma` and `/tabulka/rozsah/venku`.
- Administrator menu item forms with project-scoped dependent selectors.
- Improved menu type and field language constants for Czech, English and German.
- iCal calendar feed output with proper calendar response handling.
- Ranking, result, schedule and team pages aligned with the modern frontend layout.

## Upgrade Notes

Install the package ZIP through Joomla's extension installer or through the Joomla update manager once the update server discovers the release.

After installation:

1. Clear Joomla cache if the administrator does not immediately show the new menu form labels.
2. Re-save project-scoped menu items if they were created during earlier alpha versions with incomplete parameters.
3. Verify frontend menu items for project, standings, results, schedule, teams, rosters and match reports.
4. Check generated canonical links on public project pages if the site uses multilingual aliases.

Existing query-style URLs remain parseable where practical. Newly generated links prefer path segments.

## Main Changes Since 6.1.0-alpha-61

### Routing and SEO

- Added project-aware SEF URL generation for project sections and entity detail pages.
- Added translated route aliases for Czech, English and German.
- Added canonical links for public pages.
- Added a system plugin that maps translated top-level aliases such as `/competitions` and `/wettbewerbe` to the canonical Joomla menu route without redirects.
- Removed redundant query parameters from generated team, roster, rivals, team statistics and match-report URLs.
- Added clean schedule and standings filter URL segments.

### Menu Item Administration

- Added dynamic menu item option fields for project-scoped views.
- Scoped dependent selectors to the selected project for teams, matches, rounds, event types, statistics and tournament trees.
- Replaced raw global club selection with an administrator autocomplete field.
- Split administrator menu constants from public site constants.
- Added Czech, English and German translations for menu types and menu fields.

### Frontend Pages

- Grouped match result pages by rounds.
- Ordered result round sections by match dates where imported round codes do not match the actual chronology.
- Restored standings score as `goals for:goals against` and added a signed `+/-` column.
- Removed redundant local subnavigation blocks from several project pages.
- Improved breadcrumbs and page titles for project and team detail pages.
- Improved ranking curve rendering and the ranking-by-round table.

### Calendar and Integrations

- Fixed iCal feed responses so calendar clients receive a clean `text/calendar` response.
- Kept calendar links available for Google, Apple, Outlook.com and Office 365.
- Added Smart Search support and bundled integration plugins in the installable package.

### Running Race Support

- Added the initial running-race project type.
- Added race categories, participants and race results.
- Added race result recalculation and frontend race results filters.

### Template Configuration

- Added generated administrator template forms for several frontend views.
- Moved newly saved template configuration to JSON parameters.
- Preserved compatibility for imported legacy template configuration where practical.

## Known Alpha Notes

- This is an alpha build. Test on a staging copy before using it for a live migration.
- Legacy imported data can still contain language constant values; these are translated through the language layer where supported.
- Some administrator language text remains under active review and may change before the stable release.

## Download and Verification

Release assets include:

- `pkg_joomleague-6.1.0-alpha-150.zip`
- `joomleague-update.xml`
- `joomleague-changelog.xml`

The Joomla update XML contains the SHA-256 checksum for package verification.
