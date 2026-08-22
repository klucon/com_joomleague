# ADR 0015: Canonical logo model

Date: 2026-08-08

## Status

Accepted

## Context

JoomLeague 3 stored three independently managed club logo paths (`logo_small`, `logo_middle`, `logo_big`). It stored a team `picture`, but that field represents a team photograph rather than a logo. Reusing it as a logo would conflate two different media identities.

## Decision

- A club stores one nullable source image in `club.logo`.
- A team stores one nullable source image in `team.logo`.
- `team.picture` remains a separate team photograph.
- A team without its own logo may inherit its club logo in presentation code.
- Display sizes and responsive variants are derived from the one source logo; they are not independent database fields.
- New media paths do not contain the legacy `database` directory.

For JoomLeague 3 migration, `club.logo_big` maps directly to `club.logo`. The old middle and small paths do not become canonical logo variants. JoomLeague 3 has no separate team logo, so migrated teams leave `team.logo` empty.

## Database compatibility

MariaDB and PostgreSQL schemas add the same nullable `VARCHAR(255)` `logo` column to club and team tables. Update `6.2.0-20260808.4` is additive and preserves `team.picture`.

## Consequences

Code must never treat `team.picture` as a logo. Media rendering may generate or cache display variants, but those variants are implementation artifacts rather than entity fields.

## Review limitation