# Changelog

All notable changes to this project will be documented in this file.

## 6.1.0-alpha-154-dev - 2026-07-19

### Fixed

- Renamed the development package version from `6.1.0-dev-154` to `6.1.0-alpha-154-dev` so Joomla and PHP version comparison treat it as newer than `6.1.0-alpha-153` while still lower than a future public `6.1.0-alpha-154` release.
- Adjusted the team `info` column update statement so Joomla Database Checker parses the modified column name correctly.
- Normalized public `info` and internal `notes` text column declarations so Joomla Database Checker compares the same attributes on fresh installs and updates.
- Added installer schema repair for public `info` text columns so repeated installations of the same development version can still fix older alpha/dev database structures.
- Added installer schema compatibility for club and stadium latitude/longitude columns used by administrator club and stadium lists.
- Added public `info` fields for clubs and stadiums and changed team `info` to a long text field suitable for editor content.
- Changed project team `info` to a long text field so project-specific public editor content is not truncated.
- Used project-specific team public information on team detail and roster pages, with the global team information as a fallback.
- Standardized public information versus internal notes handling across person, team, project team, club and stadium forms: `info` is public editor content, `notes` remains an internal textarea.
- Clarified the remaining administrator-only notes fields for divisions, statistics and race participants so they no longer read as public descriptions.
- Added administrator list previews for public club and stadium information and kept race participant notes under their own internal fieldset label.
- Removed frontend fallbacks that displayed internal team notes on team detail and roster pages.
- Rendered public team, person, club and stadium information through Joomla content preparation and kept structured-data descriptions as plain text.
- Prevented long editor content from being embedded in project team assignment labels and administrator team list cells.
- Synced the migration tool target schema with the current component install schema so migrated databases include the same columns as fresh installations.
- Includes the current development module modernization work for birthday, matches, ticker, logo and related project-scoped module selectors.

## 6.1.0-alpha-153 - 2026-07-18

### Fixed

- Added a public release rollup for installations upgrading from the previous public `6.1.0-alpha-151` package.
- Added administrator language package management with translation status, download, update and remove actions.
- Added generated language ZIP packages for Joomla language tags supported by the translation workflow.
- Kept the main package English-only while publishing optional language packages separately.
- Added release download pages, release JSON, checksums and language package manifests for the KLUCON download/update endpoints.
- Improved administrator dashboard language status reporting and linked it to the language package management screen.
- Added AJAX project-team assignment for project setup so teams can be added one by one in scheduling order.
- Added AJAX person assignment workflows for team players and team staff.
- Added project-team player and staff counts to project team management actions.
- Improved administrator back-navigation and toolbar handling for match data sections.
- Renamed compact administrator match action columns to reduce horizontal overflow.
- Reworked round list result status display to use Joomla state-style icons instead of a duplicate results link.
- Fixed saving the same staff person in multiple roles for the same project team.
- Generated team-player aliases from the assigned person's name instead of generic sequential aliases.
- Improved person, team-player and team-staff administrator ordering, labels and translated position selectors.
- Added support for text-only external match event persons so opponent scorers and carded players can be recorded without creating global person records.
- Added support for text-only match referees and displayed referee roles consistently in match reports.
- Sorted match event rows by minute after saving and in frontend match reports.
- Added running score display for goal events in match reports.
- Reworked frontend match report layout with compact lineups, referees, match events and linked staff/person output.
- Improved match report editor layout so pre-match and match report editors can use the full available width.
- Added match-report structured data for SportsEvent, teams, players, coaches, referees, venue, address, geo data, event series and match events.
- Fixed structured data event status values for Schema.org validators and Google rich-result parsing.
- Removed duplicate `competitor` team expansion from match-report structured data.
- Avoided placeholder logo output in structured data when a real club/team logo is not available.
- Added consistent absolute URLs and `Itemid` handling in structured data links where Joomla routing context is available.
- Added frontend structured data coverage to project, projects, club, clubs, team, person, roster, ranking, schedule, results matrix, statistics, event rankings, result rankings, rivals, race results and other public views.
- Improved frontend table responsiveness while keeping data as real scrollable tables on mobile.
- Kept frontend match listings as responsive tables on mobile instead of rendering separate match cards.
- Removed the redundant mobile match-card output from results, grouped results and schedule views.
- Added a stable minimum width for match tables so the Detail action remains fully visible inside horizontal scrolling tables.
- Improved ranking table favourite-team full-row highlighting.
- Improved ranking curve display colours, point rendering and the ranking-by-round table scroll behaviour.
- Added match table date-based round ordering where imported round numbers do not match chronological order.
- Fixed grouped results and results-and-standings output to use round-separated match tables consistently.
- Added standings score display as goals-for:goals-against with a separate signed goal-difference column.

## 6.1.0-alpha-152 - 2026-07-15

### Fixed

- Fixed the default administrator season list ordering so newest season records are shown first.
- Fixed the default administrator club list ordering so clubs are shown alphabetically by name.
- Fixed the default administrator team list ordering so teams are shown alphabetically by name.
- Fixed empty club and playground latitude/longitude form values being saved as empty strings instead of `NULL`.
- Fixed sport type constants in administrator form selectors by translating sports type SQL field values.
- Added the team info value to the administrator teams list.
- Replaced the favourite project team ID text field with a project-team selector in the project appearance settings.
- Fixed untranslated sport type constants in administrator project panel and project-context headers.
- Displayed team info in project team assignment labels, using the format `Team (info) - Club`.
- Replaced the project team duallist workflow with an AJAX autocomplete that assigns teams one by one and preserves ordering for scheduling.
- Fixed untranslated contact placeholder and position names in the administrator person form.
- Clarified person form country labels by separating nationality/person country from contact address country.
- Replaced the team-player position selector with project-scoped translated positions sorted alphabetically by translated label.
- Fixed saving persons with empty height or weight fields by storing them as `NULL`.
- Replaced the person position selector with translated positions sorted alphabetically by translated label.
- Updated the administrator persons list to display last name before first name, use registration number instead of nickname, and default to last-name ascending ordering.
- Split administrator person names into separate last-name and first-name columns and applied Czech collation for person name ordering.

## 6.1.0-alpha-151 - 2026-07-15

### Fixed

- Public alpha package baseline after `6.1.0-alpha-150`.

## 6.1.0-alpha-150 - 2026-07-12

### Fixed

- Added translated SEF URL segments for standings scope values while keeping legacy `home`, `away` and `total` segments parseable.

## 6.1.0-alpha-149 - 2026-07-12

### Fixed

- Added translated SEF URL segments for schedule display mode and home/away filters instead of generating `plan` and `filter` query parameters.
- Kept legacy schedule query parameters parseable while generated schedule links use path segments.

## 6.1.0-alpha-148 - 2026-07-12

### Fixed

- Removed redundant query parameters from generated SEF links for project-scoped team, roster, rivals, team statistics and match-report pages.

## 6.1.0-alpha-147 - 2026-07-12

### Added

- Added a system plugin that internally maps translated JoomLeague base SEF aliases such as `/competitions` and `/wettbewerbe` to the canonical Joomla menu alias without issuing redirects.
- Added canonical links for public JoomLeague pages so translated alias URLs point search engines back to the canonical generated route.

## 6.1.0-alpha-146 - 2026-07-12

### Fixed

- Hardened SEF route parsing for translated URL segments so Czech, English and German route aliases remain accepted independently of the currently active frontend language.
- Extended project-section route aliases for German URL segments and kept existing Czech production URLs stable.

## 6.1.0-alpha-145 - 2026-07-12

### Changed

- Separated administrator menu item type constants from public site text constants for all JoomLeague menu layouts.
- Added dedicated administrator menu field constants for menu item parameters, keeping menu form labels independent from public site labels.
- Added Czech, English and German translations for the new administrator menu type and menu field constants.

## 6.1.0-alpha-144 - 2026-07-12

### Fixed

- Translated person position constants in the person profile page and structured data output.

## 6.1.0-alpha-143 - 2026-07-12

### Fixed

- Scoped match-report menu item match selector to the selected project.

## 6.1.0-alpha-142 - 2026-07-12

### Fixed

- Completed menu item settings audit for remaining project-scoped views.
- Scoped roster and rivals menu item team selectors to the selected project.
- Scoped results-and-standings round selector to the selected project.
- Scoped prediction game selector to the selected project.
- Marked project selectors as required for project-scoped menu item types.

## 6.1.0-alpha-141 - 2026-07-12

### Fixed

- Scoped tournament-tree menu item tree selector to the selected project.

## 6.1.0-alpha-140 - 2026-07-12

### Changed

- Changed the team-statistics menu item settings to require a project and select a project team from that project.

## 6.1.0-alpha-139 - 2026-07-12

### Changed

- Changed the team menu item settings to require a project and select a project team from that project.

## 6.1.0-alpha-138 - 2026-07-12

### Fixed

- Scoped statistics-ranking menu item statistic and team selectors to the selected project.

## 6.1.0-alpha-137 - 2026-07-12

### Fixed

- Added missing standings menu scope translations to administrator language files for Czech, English and German.

## 6.1.0-alpha-136 - 2026-07-12

### Changed

- Removed the redundant club selector from the schedule menu item settings; project team remains the project-scoped menu filter.

## 6.1.0-alpha-135 - 2026-07-12

### Fixed

- Scoped schedule menu item team and club selectors to the selected project instead of listing global records.

## 6.1.0-alpha-134 - 2026-07-12

### Fixed

- Scoped ranking-curve menu item division and compared team selectors to the selected project instead of listing global records.

## 6.1.0-alpha-133 - 2026-07-12

### Fixed

- Scoped next-match menu item division, team and match selectors to the selected project instead of listing global records.

## 6.1.0-alpha-132 - 2026-07-12

### Fixed

- Scoped iCal menu item team and club selectors to the selected project instead of listing all project teams and clubs.

## 6.1.0-alpha-131 - 2026-07-12

### Fixed

- Fixed the project teams AJAX endpoint so missing `division` request parameters default to `0` instead of causing a type error.

## 6.1.0-alpha-130 - 2026-07-12

### Fixed

- Reused the generic dynamic menu option field for the events ranking project-team selector and simplified project-team option loading.

## 6.1.0-alpha-129 - 2026-07-12

### Fixed

- Added Joomla fancy-select synchronization to the events ranking project-team menu selector.

## 6.1.0-alpha-128 - 2026-07-12

### Added

- Added a dedicated project-team menu selector field for project-scoped menu parameters.

## 6.1.0-alpha-127 - 2026-07-12

### Fixed

- Preloaded dynamic menu option fields from the saved menu item link so existing project-scoped menu items can render dependent options server-side.

## 6.1.0-alpha-126 - 2026-07-12

### Fixed

- Made dynamic menu option fields re-check parent field values after Joomla form initialization and delayed UI updates.

## 6.1.0-alpha-125 - 2026-07-12

### Fixed

- Made dynamic menu option fields locate parent request fields more robustly in Joomla menu item forms.

## 6.1.0-alpha-124 - 2026-07-12

### Fixed

- Added missing race-results menu item type translations to administrator system language files.

## 6.1.0-alpha-123 - 2026-07-12

### Added

- Added dependent menu item option fields for events ranking project, event type, project team and match filters.

## 6.1.0-alpha-122 - 2026-07-12

### Fixed

- Adjusted club autocomplete hover styling to avoid light hover backgrounds in dark administrator mode.

## 6.1.0-alpha-121 - 2026-07-12

### Fixed

- Changed club autocomplete dropdown styling to use administrator template color variables instead of fixed light colors.

## 6.1.0-alpha-120 - 2026-07-12

### Fixed

- Improved club autocomplete dropdown layering, borders and row styling in menu item forms.

## 6.1.0-alpha-119 - 2026-07-12

### Added

- Replaced the club menu item SQL list with an AJAX autocomplete selector and added matching administrator translations.

## 6.1.0-alpha-118 - 2026-07-11

### Fixed

- Added missing administrator filter forms for tournament trees and prediction games list views.

## 6.1.0-alpha-117 - 2026-07-11

### Fixed

- Split the ranking-by-round section into fixed team and independently scrolling round tables so round values cannot render in front of team names.

## 6.1.0-alpha-116 - 2026-07-11

### Fixed

- Prevented ranking-by-round table cells from bleeding under the sticky team column while horizontally scrolling.

## 6.1.0-alpha-115 - 2026-07-11

### Fixed

- Improved the ranking-by-round table under the ranking curve with round-based width, horizontal scrolling and a sticky team column.

## 6.1.0-alpha-114 - 2026-07-11

### Fixed

- Expanded ranking curve colors and rendered curve points as fixed circular dots instead of stretched SVG circles.

## 6.1.0-alpha-113 - 2026-07-11

### Fixed

- Added translated player position constants and rendered roster positions through Joomla language translation.

## 6.1.0-alpha-112 - 2026-07-11

### Fixed

- Expanded team-detail breadcrumbs and page titles with the teams overview and concrete team name instead of only the generic team view title.

## 6.1.0-alpha-111 - 2026-07-11

### Fixed

- Set component page titles from translated view names and the current project instead of leaving project pages titled by the parent menu item.

## 6.1.0-alpha-110 - 2026-07-11

### Fixed

- Removed local project navigation blocks from result matrix, schedule, statistics ranking, events ranking and ranking curve pages.
- Added project-scoped component breadcrumb entries so breadcrumbs include the current project and page title.

## 6.1.0-alpha-109 - 2026-07-11

### Fixed

- Restored the route helper import used by team links on the results-and-standings page.

## 6.1.0-alpha-108 - 2026-07-11

### Fixed

- Displayed results-and-standings matches in grouped round sections and aligned its ranking table with the standalone ranking layout.
- Removed the extra local project navigation from the results-and-standings page for consistent page headers.

## 6.1.0-alpha-107 - 2026-07-11

### Fixed

- Restored ranking score as `goals for:goals against` and displayed signed goal difference in a separate `+/-` column.

## 6.1.0-alpha-106 - 2026-07-11

### Fixed

- Split ranking goals into `+` and `-` columns and made goal difference optional in the ranking template settings.

## 6.1.0-alpha-105 - 2026-07-11

### Fixed

- Ordered grouped result rounds by their first match date instead of the visible round number.

## 6.1.0-alpha-104 - 2026-07-11

### Fixed

- Ordered result round sections by the visible round number when imported round codes do not match round names.

## 6.1.0-alpha-103 - 2026-07-11

### Fixed

- Ordered grouped result sections by round code so postponed matches do not move a round to the end.

## 6.1.0-alpha-102 - 2026-07-11

### Fixed

- Displayed match results in separate round sections instead of one flat table.

## 6.1.0-alpha-101 - 2026-07-11

### Fixed

- Generated helper SEF route segments from language constants for club, team, scope, rivals and team statistics URLs.

## 6.1.0-alpha-100 - 2026-07-11

### Fixed

- Kept project-specific views without an explicit `project_id` from silently loading the latest published project.

## 6.1.0-alpha-99 - 2026-07-11

### Fixed

- Parsed project section route aliases correctly when the active menu item already strips the `/competitions` base segment.

## 6.1.0-alpha-98 - 2026-07-11

### Fixed

- Accepted canonical project section route aliases when parsing SEF URLs like `/competitions/race-results` without a selected project.

## 6.1.0-alpha-97 - 2026-07-11

### Fixed

- Used the singular club menu item as the base path for club detail URLs without falling back to `/component/joomleague`.

## 6.1.0-alpha-96 - 2026-07-11

### Fixed

- Prevented the club list menu item from being used as the base path for club detail URLs.

## 6.1.0-alpha-95 - 2026-07-11

### Fixed

- Used singular `/club/{id-alias}` URLs for club detail menu items.
- Routed project section menu items without a selected project under `/competitions/{section}` instead of short top-level aliases.

## 6.1.0-alpha-94 - 2026-07-11

### Fixed

- Avoided `/component/joomleague` fallback URLs for venue detail menu items.

## 6.1.0-alpha-93 - 2026-07-11

### Fixed

- Generated canonical SEF links for venue, team rivals and team statistics menu items.

## 6.1.0-alpha-92 - 2026-07-11

### Fixed

- Generated canonical SEF links for entity detail menu items such as club, team, roster, match report, person and venue instead of using short menu aliases.

## 6.1.0-alpha-91 - 2026-07-11

### Fixed

- Encoded project section filters such as club, team, standings scope and ranking-curve teams as SEF path segments instead of query strings.

## 6.1.0-alpha-90 - 2026-07-11

### Changed

- Removed experimental redirects from short project-section menu URLs while keeping canonical menu link generation.

## 6.1.0-alpha-89 - 2026-07-11

### Fixed

- Stabilized SEF route generation for project-scoped menu items before removing temporary redirect rules.
- Kept short menu aliases parseable while canonical component links continued to prefer project-aware URLs.

## 6.1.0-alpha-88 - 2026-07-11

### Fixed

- Prefer canonical project-section routes over exact menu aliases so project-specific links keep the `/competitions/{project}/{section}` URL shape even when matching menu items exist.

## 6.1.0-alpha-87 - 2026-07-11

### Fixed

- Force native HTTP headers for iCal responses so calendar clients receive `text/calendar`.

## 6.1.0-alpha-86 - 2026-07-11

### Fixed

- Send iCal feed responses directly as `text/calendar` instead of rendering them through the normal HTML document pipeline.

## 6.1.0-alpha-85 - 2026-07-11

### Fixed

- Added support for legacy imported language keys that contain hyphens, including yellow-red card event names.

## 6.1.0-alpha-84 - 2026-07-11

### Fixed

- Translated legacy imported sport and event constants on public project, team, club, race, event ranking and match detail pages instead of rendering raw language keys.

## 6.1.0-alpha-83 - 2026-07-11

### Fixed

- Fixed SEF routing for project-scoped section links such as result matrix, next match, rankings, ranking curve and tournament tree. Generic menu items without a project ID no longer swallow concrete project URLs.

## 6.1.0-alpha-82 - 2026-07-11

### Fixed

- Fixed SEF routing for team detail links generated from result lists and other project pages. Generic menu items without a team ID no longer swallow concrete `team`/`roster` URLs.

## 6.1.0-alpha-81 - 2026-07-11

### Fixed

- Hid the raw JSON template-configuration textarea from the administrator template editor.
- Added generated clickable Joomla form definitions for legacy project-template configuration types.
- Kept template configuration stored as JSON while allowing imported legacy INI values to be converted on first administrator save.
- Added missing language fallbacks for generated template form labels in English, Czech and German.

## 6.1.0-alpha-80 - 2026-07-11

### Added

- Added project-template display forms for standings, results, squad lists and match reports. Each setting is editable in the administrator interface and is stored as JSON for the selected project.
- Added configurable standings scopes (overall, home and away) and column visibility.
- Added configurable result-list, squad-list and match-report display options, including venue, match detail links, shirt numbers, country flags, match metadata, events and referees.

### Changed

- Made `#__joomleague_template_config.params` strictly JSON-only for all newly saved template configuration. XML is used only to define Joomla administrator form fields.
- Replaced template bootstrap defaults previously stored as INI-like strings with JSON objects.

## 6.1.0-alpha-79 - 2026-07-10

### Fixed

- Cleaned up generated administrator template form definitions before enabling the JSON-only template configuration workflow.
- Added safer fallbacks for projects that do not yet have saved frontend template parameters.

## 6.1.0-alpha-78 - 2026-07-10

### Fixed

- Normalized imported frontend template parameter values so legacy display settings can be represented consistently in Joomla 6 forms.
- Improved template bootstrap handling for projects created before the new template editor was introduced.

## 6.1.0-alpha-77 - 2026-07-10

### Added

- Added additional frontend template configuration metadata used by standings, results, roster and match-report layouts.
- Added language keys for generated template configuration labels across the supported administrator languages.

## 6.1.0-alpha-76 - 2026-07-10

### Fixed

- Improved administrator template configuration loading when a project uses inherited or missing template rows.
- Kept template editor state isolated per project to avoid leaking settings between competitions.

## 6.1.0-alpha-75 - 2026-07-10

### Changed

- Prepared the template-configuration storage layer for generated Joomla form fields and JSON parameter output.
- Aligned template configuration defaults with the modern frontend layouts.

## 6.1.0-alpha-74 - 2026-07-08

### Fixed

- Included the site `layouts` folder in the component manifest so shared frontend layouts generated during the latest rewrite are installed with the package.
- Rebuilt the complete package on top of the `6.1.0-alpha-72` sports-type schema and sports-bootstrap fixes.

## 6.1.0-alpha-73 - 2026-07-08

### Fixed

- Rebuilt the Joomla package after the sports-bootstrap and layout installation changes to verify the installer payload.
- Kept component, module and plugin manifests aligned with the package version.

## 6.1.0-alpha-72 - 2026-07-08

### Added

- Added an "All sports" sports-bootstrap profile so the Joomla core component options can initialize every bundled sport profile in one action.

### Fixed

- Kept the sports-type schema repair from `6.1.0-alpha-71` for migrated Joomla 3 databases where `#__joomleague_sports_type.published` is missing.

## 6.1.0-alpha-71 - 2026-07-08

### Fixed

- Fixed migrated sports type tables missing the `published` column after Joomla 3 data import.
- Added a schema repair step for `#__joomleague_sports_type` to normalize engine, charset and required columns.
- Kept sports bootstrap available from Joomla core component options.

### Notes

- Imported legacy language constants stored in data must be preserved and translated through the language layer instead of being rendered as raw constant names.

## 6.1.0-alpha-70 - 2026-07-08

### Added

- Added the initial running-race competition model for GitHub issue #16.
- Added the `RUNNING_RACE` project type.
- Added administrator CRUD for race categories, runners and race results.
- Added race result ranking recalculation.
- Added frontend race results view with filters for round, category, sex and result status.
- Added race-specific database tables and update SQL.

### Changed

- Aligned package, component, modules and plugins to version `6.1.0-alpha-70`.

## 6.1.0-alpha-69 - 2026-07-07

### Fixed

- Prepared the running-race update path with safer installer checks for existing Joomla 6 databases.
- Kept package validation green while introducing the race-specific schema changes.

## 6.1.0-alpha-68 - 2026-07-07

### Added

- Added administrator language coverage for the running-race project type, race categories and race result management screens.
- Added frontend language entries for race result filters and result status labels.

## 6.1.0-alpha-67 - 2026-07-07

### Fixed

- Improved race result recalculation handling for empty categories and unpublished race records.
- Added defensive checks around race project lookups in administrator and site views.

## 6.1.0-alpha-66 - 2026-07-07

### Added

- Added the first public frontend race-results layout with round, category, sex and status filters.
- Added routing support for the race-results project section.

## 6.1.0-alpha-65 - 2026-07-07

### Added

- Added race participant and race result administrator models, tables and list views.
- Added race result status handling used by running-race result calculations.

## 6.1.0-alpha-64 - 2026-07-07

### Added

- Added race category management for running-race projects.
- Added race-specific update SQL so existing installations can receive the new schema incrementally.

## 6.1.0-alpha-63 - 2026-07-07

### Added

- Added the `RUNNING_RACE` project type plumbing to project records, administrator forms and frontend project handling.
- Prepared project type checks used by race-specific frontend sections.

## 6.1.0-alpha-62 - 2026-07-07

### Changed

- Prepared the package metadata and installer sequence for the running-race milestone.
- Kept the Joomla update metadata compatible with the existing package update server.

## 6.1.0-alpha-61 - 2026-07-06

### Fixed

- Fixed a fatal error on the tournament tree page for competitions that have no knockout tree (for example round-robin leagues). The page now shows a friendly "not found" message instead of failing.

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

## 6.1.0-alpha-59 - 2026-07-06

### Fixed

- Finalized the telemetry consent card layout and administrator feedback before the public alpha-60 package.
- Verified that the telemetry workflow remains opt-in and can be changed from the component configuration.

## 6.1.0-alpha-58 - 2026-07-06

### Added

- Added administrator configuration storage for the telemetry consent choice and recurring monthly heartbeat option.
- Added delivery status messages for send-once telemetry submissions.

## 6.1.0-alpha-57 - 2026-07-06

### Added

- Added the telemetry payload builder with strict privacy boundaries and anonymous installation identifiers.
- Added safeguards so telemetry is never sent before an administrator explicitly chooses an option.

## 6.1.0-alpha-56 - 2026-07-06

### Fixed

- Improved the next-match preview layout with recent form blocks and comparison tables.
- Added fallbacks for projects without enough previous matches to build a complete form sequence.

## 6.1.0-alpha-55 - 2026-07-06

### Added

- Added club header logo rendering and privacy-friendly map links for clubs and venues.
- Removed the need for legacy embedded-map dependencies in the modern club page.

## 6.1.0-alpha-54 - 2026-07-06

### Added

- Added self-contained team statistics charts for results overview and goals overview.
- Replaced legacy Flash-based chart output with HTML/CSS rendering.

## 6.1.0-alpha-53 - 2026-07-06

### Fixed

- Improved frontend parity for team statistics, next match and club views before the telemetry milestone.
- Added missing translations used by the refreshed frontend cards and summary blocks.

## 6.1.0-alpha-52 - 2026-07-06

### Changed

- Refined the Joomla 6 frontend rewrite after the menu-item picker milestone.
- Kept generated frontend links routed through the component router instead of raw query strings where supported.

## 6.1.0-alpha-51 - 2026-07-06

### Fixed

- Stabilized menu-item picker handling after the alpha-50 release candidate.
- Improved missing-record handling for menu items that point to deleted or migrated records.

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
