<div align="center">

# JoomLeague for Joomla 6

**Sports league and competition management for Joomla 6.**

JoomLeague helps clubs, leagues and tournament organizers manage seasons, competitions, clubs, teams, people, fixtures, results, standings, statistics, calendars and frontend publishing from one Joomla package.

<br>

[![Release](https://img.shields.io/github/v/release/klucon/com_joomleague?style=flat-square&logo=github&label=release&color=2ea44f)](https://github.com/klucon/com_joomleague/releases/latest)
[![Build](https://img.shields.io/github/actions/workflow/status/klucon/com_joomleague/build-package.yml?style=flat-square&logo=githubactions&logoColor=white&label=build)](https://github.com/klucon/com_joomleague/actions)
[![License](https://img.shields.io/github/license/klucon/com_joomleague?style=flat-square&label=license&color=2563eb)](LICENSE)
[![Joomla](https://img.shields.io/badge/Joomla-6.1%2B-5091CD?style=flat-square&logo=joomla&logoColor=white)](https://www.joomla.org)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![Languages](https://img.shields.io/badge/languages-EN%20%7C%20CS%20%7C%20DE-f59e0b?style=flat-square)](#languages)

<br>

**Public ecosystem**

**Project:** [Website](https://klucon.cz) · [Downloads](https://klucon.cz/downloads) · [Documentation](https://docs.klucon.cz) · [Live demo](https://joomleague.klucon.cz)

**Services:** [Public statistics](https://stats.klucon.cz) · [Migration tool](https://migrate.klucon.cz) · [Community forum](https://forum.klucon.cz) · [Latest release](https://github.com/klucon/com_joomleague/releases/latest)

</div>

---

## About

JoomLeague is a Joomla 6 package for publishing sports competitions on a Joomla website. It is built around the `com_joomleague` component and includes site modules, content plugins, Smart Search integration and administrator helpers.

The current development line is a Joomla 6 modernization of the classic JoomLeague concept. It uses namespaced Joomla MVC code, Joomla 6 form APIs, administrator menu item configuration, multilingual SEF routing and frontend templates designed for modern Joomla sites.

Live demo: [joomleague.klucon.cz](https://joomleague.klucon.cz)

## Current Release

| Item | Value |
|------|-------|
| Current version | `6.1.0-alpha-151` |
| Release date | 2026-07-15 |
| Joomla support | Joomla 6.1 or newer |
| PHP support | PHP 8.3 or newer |
| Update channel | GitHub Releases and Joomla update server |
| Package asset | `pkg_joomleague-6.1.0-alpha-151.zip` |

This is an alpha release. Test upgrades, menu items, imported data and frontend output on a staging copy before updating a live site.

Release notes:

- [Latest GitHub release](https://github.com/klucon/com_joomleague/releases/latest)
- [Public changelog](https://klucon.cz/changelog)
- [Documentation changelog](https://docs.klucon.cz/changelog)

## Main Features

### Competition Management

- Sports, leagues, seasons and competition projects
- Clubs, teams, players, staff, referees and other people
- Rounds, matches, results, rosters and lineups
- Event types, playing positions and statistics
- Playgrounds, stadiums and tournament trees
- Prediction games with scoring and rankings
- Running-race groundwork with categories, participants and results

### Frontend Pages

- Competition overview pages
- Standings, results, results-and-standings and result matrix
- Schedule views by round or by date
- Team lists, team detail pages, rosters, rivals and team statistics
- Club, person, playground, referee and match report pages
- Event rankings, statistics rankings and ranking curves
- iCal calendar feeds for external calendar clients

### Administrator Experience

- Project-scoped menu item configuration
- Dependent selectors for project teams, matches, rounds, statistics and event types
- AJAX autocomplete for large club lists
- Template configuration forms for frontend views
- SQL maintenance tooling
- Geocoding and map helper groundwork
- Joomla Smart Search indexing support

## SEO and SEF URLs

JoomLeague generates project-aware SEF URLs for public views and keeps canonical links focused on the generated route.

Examples:

| Language | Example |
|----------|---------|
| English | `/competitions/{project}/standings` |
| English | `/competitions/{project}/schedule/by-date` |
| English | `/competitions/{project}/teams/{team}/roster` |
| Czech | `/souteze/{project}/tabulka` |
| Czech | `/souteze/{project}/rozpis/podle-data` |
| German | `/wettbewerbe/{project}/tabelle` |
| German | `/wettbewerbe/{project}/spielplan/nach-datum` |

Legacy query-style links remain parseable where practical, but newly generated frontend links prefer clean path segments.

## Requirements

| Requirement | Version |
|-------------|---------|
| Joomla | 6.1 or newer |
| PHP | 8.3 or newer |
| Database | MySQL 8.0+ or MariaDB 10.4+ |

## Installation

1. Download the latest package from [klucon.cz/downloads](https://klucon.cz/downloads) or from [GitHub Releases](https://github.com/klucon/com_joomleague/releases/latest).
2. Open the Joomla administrator.
3. Go to **System** -> **Install** -> **Extensions**.
4. Upload `pkg_joomleague-6.1.0-alpha-151.zip`.
5. Open **Components** -> **JoomLeague** and configure your first project.

After installation, the package registers a Joomla update site. Future releases can be installed from **System** -> **Update** -> **Extensions**.

## Package Contents

### Component

- `com_joomleague`

### Site Modules

- `mod_joomleague_birthday`
- `mod_joomleague_calendar`
- `mod_joomleague_eventsranking`
- `mod_joomleague_logo`
- `mod_joomleague_matches`
- `mod_joomleague_navigation_menu`
- `mod_joomleague_playgroundplan`
- `mod_joomleague_randomplayer`
- `mod_joomleague_ranking`
- `mod_joomleague_results`
- `mod_joomleague_sports_type_statistics`
- `mod_joomleague_statranking`
- `mod_joomleague_teamplayers`
- `mod_joomleague_teamstaffs`
- `mod_joomleague_teamstats_ranking`
- `mod_joomleague_ticker`

### Plugins

- `content/joomleaguematch`
- `content/joomleagueperson`
- `extension/joomleagueesport`
- `finder/joomleague`
- `quickicon/joomleague`
- `system/joomleaguesefaliases`

Bundled integration plugins are enabled automatically during package installation where required by the package.

## Languages

The package includes language files for:

| Language | Tag |
|----------|-----|
| English | `en-GB` |
| Czech | `cs-CZ` |
| German | `de-DE` |

Translations cover the component, modules and plugins on both the site and administrator side.

## Documentation

- Documentation: [docs.klucon.cz](https://docs.klucon.cz)
- Public website: [klucon.cz](https://klucon.cz)
- Forum: [forum.klucon.cz](https://forum.klucon.cz)
- Demo: [joomleague.klucon.cz](https://joomleague.klucon.cz)
- Public statistics: [stats.klucon.cz](https://stats.klucon.cz)
- Migration tool: [migrate.klucon.cz](https://migrate.klucon.cz)

## Development

The source tree keeps the package extensions unpacked for development. To build the installable package:

```bash
python3 build/validate_versions.py
python3 build/package.py
python3 build/validate_package.py
```

Build output is written to `dist/`.

Useful release metadata commands:

```bash
python3 build/release_metadata.py
```

This generates the Joomla update XML and changelog XML files used by the release process.

## Roadmap

Planned work for upcoming alpha releases:

- Continue replacing legacy frontend layouts with shared Joomla 6 helpers and templates.
- Expand administrator template configuration coverage.
- Improve import and migration tooling for older JoomLeague installations.
- Refine map, venue and geocoding workflows.
- Continue the language constant cleanup so each UI context has its own translation key.
- Broaden automated validation for routing, menu item configuration and package metadata.

## Contributing

Bug reports, testing feedback and feature requests are welcome.

- Issues: [github.com/klucon/com_joomleague/issues](https://github.com/klucon/com_joomleague/issues)
- Forum: [forum.klucon.cz](https://forum.klucon.cz)
- Security policy: [SECURITY.md](SECURITY.md)

## License

JoomLeague is released under the [GNU General Public License v2.0 or later](LICENSE).

## Author

Ondřej Klučka<br>
[klucon.cz](https://klucon.cz)<br>
[info@klucon.cz](mailto:info@klucon.cz)

<div align="center">

[Website](https://klucon.cz) · [Downloads](https://klucon.cz/downloads) · [Documentation](https://docs.klucon.cz) · [Demo](https://joomleague.klucon.cz) · [Statistics](https://stats.klucon.cz) · [Migration tool](https://migrate.klucon.cz) · [Forum](https://forum.klucon.cz)

</div>
