<div align="center">

# ⚽ JoomLeague for Joomla 6

**A complete sports league &amp; competition management suite for Joomla 6.**

Manage leagues, seasons, clubs, teams, players, fixtures, results, standings, statistics and more — all from a single, modern Joomla 6 component.

<br>

[![Latest release](https://img.shields.io/github/v/release/klucon/com_joomleague?style=for-the-badge&logo=github&color=2ea44f)](https://github.com/klucon/com_joomleague/releases/latest)
[![Build](https://img.shields.io/github/actions/workflow/status/klucon/com_joomleague/build-package.yml?style=for-the-badge&logo=githubactions&logoColor=white&label=build)](https://github.com/klucon/com_joomleague/actions)
[![License](https://img.shields.io/github/license/klucon/com_joomleague?style=for-the-badge&color=blue)](LICENSE)

[![Joomla 6.1](https://img.shields.io/badge/Joomla-6.1-5091CD?style=flat-square&logo=joomla&logoColor=white)](https://www.joomla.org)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![Languages](https://img.shields.io/badge/i18n-EN%20%7C%20CS%20%7C%20DE-orange?style=flat-square)](#-languages)
[![Last commit](https://img.shields.io/github/last-commit/klucon/com_joomleague?style=flat-square&logo=git&logoColor=white)](https://github.com/klucon/com_joomleague/commits)
[![Issues](https://img.shields.io/github/issues/klucon/com_joomleague?style=flat-square&logo=github)](https://github.com/klucon/com_joomleague/issues)
[![Stars](https://img.shields.io/github/stars/klucon/com_joomleague?style=flat-square&logo=github)](https://github.com/klucon/com_joomleague/stargazers)

<br>

### 🔗 Quick links

[![Website](https://img.shields.io/badge/Website-klucon.cz-111827?style=for-the-badge&logo=googlechrome&logoColor=white)](https://klucon.cz)
[![Live Demo](https://img.shields.io/badge/Live_Demo-joomleague.klucon.cz-2ea44f?style=for-the-badge&logo=serverfault&logoColor=white)](https://joomleague.klucon.cz)
[![GitHub](https://img.shields.io/badge/Source-GitHub-181717?style=for-the-badge&logo=github&logoColor=white)](https://github.com/klucon/com_joomleague)
[![Wiki](https://img.shields.io/badge/Docs-Wiki-0969DA?style=for-the-badge&logo=readthedocs&logoColor=white)](https://github.com/klucon/com_joomleague/wiki)

</div>

---

## 📖 About

**JoomLeague** is a full-featured sports management system for the [Joomla 6](https://www.joomla.org) CMS. Whether you run a single football club, a regional league, or a multi-division tournament, JoomLeague gives you everything you need to publish a rich, always up-to-date sports website — no third-party services required.

The package has been **rebuilt from the ground up for Joomla 6**, using a modern namespaced MVC architecture, tighter security, and a clean, responsive frontend.

> 👀 **See it in action:** [joomleague.klucon.cz](https://joomleague.klucon.cz) — a live demo available in **English, Czech and German**.

---

## ✨ Features

### 🏆 Competition management
- Sport types, leagues, seasons and competition projects
- Clubs, teams and people (players, staff, referees)
- Rounds, matches, results and squad rosters
- Event types, playing positions and statistics
- Playgrounds / stadiums and tournament trees (brackets)

### 📊 Frontend for your visitors
- Project, team, club, person, playground and referee pages
- League **standings**, **results** and combined results/standings views
- **Result matrix** with full parity — played, upcoming, cancelled and forfeited matches, with division grouping
- **Smart schedule** — switch between *by round* and *by date*, plus *all / home / away* filtering for teams
- Rich **match detail** pages and **player profiles** with photo, contact details and full match history
- **Rivals** head-to-head team comparison
- Event, statistics and team-statistics **rankings**

### 🎯 Engagement &amp; integrations
- **Prediction game** — tipping competitions with automatic score recalculation and tipster leaderboards
- **iCal calendar feed** with one-click subscription for Google, Apple, Outlook.com and Office 365
- `{jlmatch}` **content shortcode** to embed any match directly inside an article
- Native Joomla **Custom Fields** support for clubs, teams and people
- **Country flags &amp; picker** with a built-in lookup of 254 countries
- **Smart Search** integration for site-wide match &amp; person indexing

### 🧩 Included extensions
The package ships as a single installable bundle containing the component, **16 site modules** and **5 integration plugins** (see [What's included](#-whats-included)).

### 🔒 Built for production
- Access-control (ACL) and record ownership checks throughout
- Safe HTML filtering on user-supplied content
- Multilingual: **English, Czech and German**
- Signed, verifiable releases via the Joomla update server (SHA-256 package integrity)

---

## ✅ Requirements

| Requirement | Version |
|-------------|---------|
| Joomla      | **6.1 or newer** |
| PHP         | **8.3 or newer** |
| Database    | MySQL 8.0+ / MariaDB 10.4+ |

---

## 🚀 Installation

1. Download the latest **package ZIP** from the [**Releases**](https://github.com/klucon/com_joomleague/releases/latest) page.
2. In your Joomla administrator, go to **System → Install → Extensions**.
3. Drag &amp; drop the ZIP onto the **Upload Package File** area.
4. Open **Components → JoomLeague** from the admin menu and start building your league. 🎉

Once installed, JoomLeague registers a Joomla **update site**, so future versions can be installed with one click from **System → Update → Extensions**.

---

## 📦 What's included

<details>
<summary><strong>Component</strong></summary>

- `com_joomleague` — administration &amp; frontend

</details>

<details>
<summary><strong>16 site modules</strong></summary>

`mod_joomleague_ranking` · `mod_joomleague_results` · `mod_joomleague_matches` · `mod_joomleague_eventsranking` · `mod_joomleague_statranking` · `mod_joomleague_teamstats_ranking` · `mod_joomleague_sports_type_statistics` · `mod_joomleague_teamplayers` · `mod_joomleague_teamstaffs` · `mod_joomleague_calendar` · `mod_joomleague_birthday` · `mod_joomleague_randomplayer` · `mod_joomleague_playgroundplan` · `mod_joomleague_navigation_menu` · `mod_joomleague_ticker` · `mod_joomleague_logo`

</details>

<details>
<summary><strong>5 integration plugins</strong></summary>

- `content/joomleaguematch` — the `{jlmatch}` match shortcode
- `content/joomleagueperson` — person embedding
- `extension/joomleagueesport` — e-sport extension
- `finder/joomleague` — Joomla Smart Search indexing
- `quickicon/joomleague` — admin control-panel quick icon

</details>

---

## 🌍 Languages

JoomLeague ships fully translated in:

| 🇬🇧 English (`en-GB`) | 🇨🇿 Čeština (`cs-CZ`) | 🇩🇪 Deutsch (`de-DE`) |
|---|---|---|

Translations cover the component, all modules and plugins on both the site and administrator side.

---

## 🗺️ Roadmap

Actively in development for upcoming releases:

- **Modernised menu-item setup** — no more typing raw IDs:
  - dropdown pickers for lookups (project, club, team, round, playground, division…)
  - searchable modal pickers for people and matches, even across thousands of records
- **Required target fields** — menu items can't be saved without a selected target
- **Graceful "not found" handling** for menu items that point to missing records
- **Completion of the remaining frontend view rewrites** (upcoming match, club detail, team statistics and additional rankings)
- **Continuous responsive polish** across mobile and desktop

---

## 📚 Documentation

Full documentation lives in the [**GitHub Wiki**](https://github.com/klucon/com_joomleague/wiki), including frontend view guides and configuration references.

---

## 🤝 Contributing

Contributions, bug reports and feature requests are welcome!

- 🐛 [Open an issue](https://github.com/klucon/com_joomleague/issues)
- 📖 Read the [Contributing guide](CONTRIBUTING.md)
- 🔐 Review the [Security policy](SECURITY.md) for responsible disclosure

---

## 🛠️ Building from source

The source tree keeps each extension unpacked for development. To build the installable package (requires **Python 3**):

```bash
python3 build/package.py
```

This produces the child-extension ZIPs and the final package in `dist/`.

---

## 📄 License

Released under the **[GNU General Public License v2.0 or later](LICENSE)**.

---

## 👤 Author

**Ondřej Klučka**
🌐 [klucon.cz](https://klucon.cz) · ✉️ [info@klucon.cz](mailto:info@klucon.cz)

<div align="center">

⭐ **If JoomLeague helps your club or league, consider starring the repository!** ⭐

[![Website](https://img.shields.io/badge/klucon.cz-111827?style=flat-square&logo=googlechrome&logoColor=white)](https://klucon.cz)
[![Demo](https://img.shields.io/badge/Live_Demo-2ea44f?style=flat-square&logo=serverfault&logoColor=white)](https://joomleague.klucon.cz)
[![GitHub](https://img.shields.io/badge/GitHub-181717?style=flat-square&logo=github&logoColor=white)](https://github.com/klucon/com_joomleague)

</div>
