# JoomLeague 6.2

**Open-source sports competition management for Joomla.**

Manage competitions, clubs, teams, people, fixtures, results and statistics in
one Joomla installation. Publish schedules, standings, match reports and career
histories through the component and its frontend modules.

JoomLeague 6.2 is a redesigned, sport-independent generation of the project.
Football is one supported sport, not the system's hardcoded data model.

[Download](https://downloads.joomleague.eu/) |
[Documentation](https://docs.joomleague.eu/) |
[Live Demo](https://demo.joomleague.eu/) |
[Support](https://support.joomleague.eu/) |
[Translations](https://translate.joomleague.eu/)

> **Release status:** this source tree targets **6.2.0 Release Candidate 2**.
> Use the download portal to identify the latest published package. Release
> candidates are intended for testing and feedback, not a stable-release guarantee.

## What You Can Manage

- **Competitions:** seasons, projects, stages, rounds, entries and schedules.
- **Sports data:** results, match events, lineups, officials and profile-driven
  statistics and standings.
- **Club directories:** clubs, teams, venues, people and contextual sporting roles.
- **Historical records:** player careers, coaching and referee records, team
  rivalries and previous seasons.
- **Public presentation:** competition and team pages, match reports and sixteen
  frontend modules for programmes, results, rankings and club information.
- **Joomla integration:** native permissions, menus, forms, Smart Search,
  content integration, scheduled tasks and console commands.

### Sport Profiles, Not Football-Only Rules

Versioned sport profiles define roles, events, statistics and competition rules.
The repository includes seventeen profiles covering football, futsal,
basketball, volleyball, ice hockey, floorball, rugby, tennis, carom billiards,
bowling, darts, chess, esports, motorsport, running races, combat sports and
team encounters.

A bundled profile is not a promise that every rule or competition format used
by every federation is implemented. Verify the required format and scoring
rules in a test installation before using them for a real competition.

One person can participate in several sports. Positions belong to the relevant
sport and assignment context; they are not a single global label on the person.

## Requirements

| Component | Requirement |
| --- | --- |
| Joomla | 6.1.x or 6.2.x |
| PHP | 8.3 or newer, subject to the installed Joomla version's requirements |
| Database | MySQL 8.0+, MariaDB 10.6+ or PostgreSQL 14+ |
| Base language | English (`en-GB`) |

The hosting environment must also meet Joomla's requirements and provide a
writable temporary directory and sufficient upload/extraction limits for the
full package. Use maintained, security-patched software versions.

## Choose the Correct Installation Path

### New Installation

1. Download the **full JoomLeague package** (`pkg_joomleague-*.zip`) from the
   [official download portal](https://downloads.joomleague.eu/).
2. Install it through **System -> Install -> Extensions** in Joomla.
3. Open **Components -> JoomLeague** and create a sport type from an appropriate
   sport profile.
4. Configure your clubs, teams, people, competition and participants before
   generating fixtures or entering results.
5. Create Joomla menu items and publish the modules needed by your website.

Use the installable ZIP, not GitHub's automatically generated **Source code**
archives. The source repository is not the assembled installation package.

### Updating an Existing 6.2 Installation

Back up both files and the database, then test the update on a copy of the site.
Use the full compatible package or Joomla's registered extension update channel;
do not manually copy source files over an installed component.

> **RC1 -> RC2 result correction:** RC2 automatically corrects supported migrated
> football results using their preserved original data. This can overwrite manual
> changes to affected results. Incomplete or contradictory source results require
> review rather than an invented score. Keep a full pre-update backup and inspect
> the installation messages and result-repair log.

An update does not automatically reconstruct catalogue records missing after an
earlier failed import. Conflicting identities, sport owners or profile bindings
must be reviewed before attempting another import.

### Moving From JoomLeague 6.1.x or Older

**JoomLeague 6.2 is not compatible with the JoomLeague 6.1.x database schema or
extension files. It is not an in-place upgrade from that generation.**

Do not install 6.2 over an older JoomLeague installation, copy legacy extension
files into it or import an unconverted legacy SQL dump directly.

Use [migrate.joomleague.eu](https://migrate.joomleague.eu/) to convert a supported
legacy dump. Import the generated migration package into a **separate, clean 6.2
test installation**, review the conversion report and verify the resulting data.

Data conversion does not make old templates, modules, plugins or database tables
compatible with the new runtime. Never post database dumps publicly: they may
contain personal data or credentials.

## Languages and Translation Packages

**Starting with RC2, the base package contains English (`en-GB`) only.**
Czech, German, Polish and other translations are maintained separately in
[JoomLeague Weblate](https://translate.joomleague.eu/).

- Obtain available language packages from the
  [download portal](https://downloads.joomleague.eu/).
- Translation packages have their own update channel, separate from the main
  component package.
- Existing installed translations and Joomla language overrides are preserved by
  the RC2 update; independently installed translations should also be kept current.
- Contribute corrections through Weblate rather than editing bundled English files.

Joomla's own interface language pack and the JoomLeague translation package are
separate installations. See the
[language distribution contract](docs/LANGUAGE-DISTRIBUTION-CONTRACT.md).

## Country Flags

The RC2 full package includes the **Klucon Countries** component dependency and
its JoomLeague connector. They provide shared country data and flag assets
instead of maintaining a separate copy of flags in every extension.

Flag rendering depends on the installed and enabled integration. Without it,
JoomLeague retains its textual country fallback rather than requiring flag
images to render the rest of the page. Installing only the standalone JoomLeague
component is not equivalent to installing the full integration package.

## Included Extensions

| Area | Included functionality |
| --- | --- |
| Main component | `com_joomleague`: administration and public sports views |
| Programme modules | Programme, Latest Results, Next Programme Event, Programme Ticker, Calendar, Venue Programme |
| Ranking modules | Standings, Event Ranking, Statistic Ranking |
| Directory modules | Club, Competitions, Participant, Personnel |
| Other modules | Birthdays, Navigation, Spotlight |
| Joomla plugins | Administrator Quick Icon, Console, Scheduler, Smart Search, Content |
| Countries integration | Klucon Countries dependency and JoomLeague connector |

Historical JoomLeague extensions are not automatically supported or included.
The presence of a module does not imply that every proposed configuration
enhancement is part of RC2.

## Updates and Release Channels

The full package registers the official Joomla update feed:

```text
https://downloads.joomleague.eu/update.xml
```

To receive RC packages on a test site, set **Minimum Extension Stability** to
**Release Candidate** in Joomla's extension-update options. Sites that should
receive only stable releases should retain **Stable**.

Use the download portal for published versions, version history and package
checksums. A development manifest or an internal build is not proof that a
release has been published or approved.

## Support and Contributions

Ask questions and report reproducible problems on the
[support forum](https://support.joomleague.eu/). Include:

- JoomLeague, Joomla, PHP and database versions;
- the relevant sport profile and competition format;
- steps to reproduce, expected behaviour and the actual result;
- screenshots or a minimal, sanitised example where useful.

Do not publish passwords, session tokens, private player data or complete
database backups. Arrange private transfer with the maintainer when sensitive
diagnostic material is necessary. Report suspected security vulnerabilities
privately rather than publishing exploit details in a public topic.

Translation contributions belong in [Weblate](https://translate.joomleague.eu/).
Code contributions should preserve the sport-independent architecture, Joomla
ACL and request protections, and equivalent MariaDB/MySQL and PostgreSQL behaviour.

## Development

Sport rules live in versioned JSON profiles. Joomla provides the surrounding
MVC, forms, permissions and extension lifecycle. Converted legacy values retain
provenance, but legacy tables are not the runtime data model.

Build the installable package from the repository root:

```bash
bash build/build-package.sh
```

Run the architecture and PHP unit checks:

```bash
php tests/Architecture/verify-foundation.php
bash tests/Architecture/verify-release.sh

for test in tests/Unit/verify-*.php; do
    php "$test" || exit 1
done
```

Integration and browser harnesses are in `tests/Integration` and `tests/Browser`.
They require their documented local dependencies and isolated test environments.
Do not point destructive acceptance fixtures at a live website.

Passing a build is not release approval. Installation, upgrade, data-integrity,
permission and browser checks must cover the exact candidate being published.

## License

JoomLeague is free and open-source software released under the
**GNU General Public License, version 2 or later**.
