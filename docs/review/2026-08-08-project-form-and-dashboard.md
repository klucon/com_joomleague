# Project form and dashboard review

Date: 2026-08-08

## Implemented

- Season, competition and project descriptions use a full-width editor row.
- The project editor uses Joomla tabs for project data, schedule, presentation and publishing.
- New projects default to automatic current-round selection at the start of the next round.
- The automatic round offset defaults to 7200 seconds, matching the established JoomLeague behavior.
- The dashboard reports persisted profiles, sport types, competitions, projects and project entries.
- Administration domains are classified centrally as `available`, `schema_ready` or `planned` by `AdminDomainCatalog`.

## Language policy

- All administrator-facing labels, descriptions and status text use `COM_JOOMLEAGUE_*` language keys.
- The only bundled source language is `en-GB`.
- Internal domain codes, status codes, icon names and route view names are implementation identifiers and are never rendered directly as interface text.
- Dashboard templates must not define their own catalogue of administration domains.

## Verification

- PHP syntax, XML forms and the architecture test suite pass.
- MariaDB and PostgreSQL fresh-install schemas use `current_round_mode = start` and `auto_advance_seconds = 7200`.
- Playwright verifies the dashboard and project, competition and season forms at desktop and mobile widths without horizontal overflow.
- The component package installs on the Joomla 6.2 MariaDB and PostgreSQL test sites.

## Review limitation