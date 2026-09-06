# JoomLeague 6.2

JoomLeague 6.2 is a new, sport-independent competition management system for
Joomla 6.1 and 6.2. The current `6.2-dev` branch is a development preview intended for
testing, schema validation and migration testing. It is not a production
release.

## Compatibility warning

**JoomLeague 6.2 is not compatible with JoomLeague 6.1.x.**

The 6.2 line uses a new database schema, versioned sport profiles and different
runtime contracts. Therefore:

- do not install a 6.2 package over JoomLeague 6.1.x;
- do not copy 6.1.x files into a 6.2 installation;
- do not import a 6.1.x database or SQL export directly into 6.2;
- do not use a 6.1.x update package as a route to 6.2.

JoomLeague 6.2 must be installed as a **clean installation**.

### Data conversion is not an upgrade

Existing data can only be transferred through the dedicated conversion service
at **[migrate.joomleague.eu](https://migrate.joomleague.eu/)**. The service reads
a supported legacy SQL dump and creates a new migration package for the 6.2
schema. This is an external extract-transform-load process, not compatibility
between 6.1 and 6.2 and not an in-place update.

Always import the generated package into a separate clean 6.2 test installation
and review its migration report before using the converted data anywhere that
matters. A successful conversion does not make 6.1 extensions, templates or
database tables compatible with 6.2.

## Current development package

- Version: `6.2.0-dev7`
- Joomla: 6.1.x or 6.2.x
- PHP: 8.3 or newer
- Databases: MySQL 8.0+, MariaDB 10.6+ or PostgreSQL 14+
- Source language: `en-GB`
- Included translation: current `cs-CZ` development translation

The installable package contains:

- `com_joomleague` - administrator and frontend component;
- `mod_joomleague_standings`, `mod_joomleague_program`, `mod_joomleague_next_event`,
  `mod_joomleague_calendar`, `mod_joomleague_programme_ticker`, `mod_joomleague_latest_results`, `mod_joomleague_birthdays`, `mod_joomleague_spotlight`, `mod_joomleague_navigation`, `mod_joomleague_participant`,
  `mod_joomleague_club`, `mod_joomleague_personnel`, `mod_joomleague_venue_program`,
  `mod_joomleague_competitions`, `mod_joomleague_statranking` and
  `mod_joomleague_eventranking` - sport-neutral frontend modules;
- `plg_quickicon_joomleague` - administrator quick icon;
- `plg_console_joomleague` - Joomla console integration;
- `plg_task_joomleague` - Joomla Scheduler integration.
- `plg_finder_joomleague` - Joomla Smart Search integration;
- `plg_content_joomleague` - JoomLeague content integration.

No other historical JoomLeague modules or plugins are part of the current 6.2
package unless they are explicitly listed above.

## Architecture

The 6.2 foundation is built around these rules:

1. Sport behavior is defined by versioned JSON sport profiles, not hardcoded
   football rules.
2. MariaDB/MySQL and PostgreSQL must provide equivalent schema and behavior.
3. Joomla core MVC, forms, ACL, Scheduler, update sites and administrator styles
   are used wherever Joomla already provides the required functionality.
4. Imported legacy values retain auditable provenance, but legacy tables are not
   the runtime model.
5. `en-GB` is the canonical language source.

The current bundle provides 15 sport profiles, universal competition stages,
entries, schedules, participants, results and standings, and versioned template
definitions.

## Clean installation

1. Download `pkg_joomleague-*.zip` from
   [downloads.joomleague.eu](https://downloads.joomleague.eu/).
2. Install it through **System -> Install -> Extensions** in a clean Joomla 6.1 or 6.2
   installation.
3. Open **Components -> JoomLeague** and create a sport type from a bundled
   profile or define your own catalogs.

Do not perform these steps on a Joomla site containing JoomLeague 6.1.x.

## Development updates

The package registers the official Joomla update feed:

`https://downloads.joomleague.eu/update.xml`

Development releases are correctly tagged as `dev`. Test installations must set
**System -> Update -> Extensions -> Options -> Minimum Extension Stability** to
**Development**. Stable sites should retain Joomla's default **Stable** value.

Each published package is checked with SHA-256, SHA-384 and SHA-512 hashes. The
feed and changelog remain publicly accessible so Joomla can update without an
interactive login.

## Building and verification

Build the complete package with:

```bash
./build/build-package.sh
```

The build validates the release contract before creating ZIP files. The main
checks can also be run separately:

```bash
php tests/Architecture/verify-foundation.php
./tests/Architecture/verify-release.sh

for test in tests/Unit/*.php; do
    php "$test"
done
```

Integration and browser tests run against both JoomLeague test installations,
one backed by MariaDB and one by PostgreSQL.

## Services

- Project hub: [joomleague.eu](https://joomleague.eu/)
- Downloads and updates: [downloads.joomleague.eu](https://downloads.joomleague.eu/)
- Documentation: [docs.joomleague.eu](https://docs.joomleague.eu/)
- Data conversion: [migrate.joomleague.eu](https://migrate.joomleague.eu/)
- Support: [support.joomleague.eu](https://support.joomleague.eu/)

## License

JoomLeague is released under the GNU General Public License version 2 or later.
