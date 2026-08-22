# JoomLeague 6.2 admin review build

- Prepared: 2026-08-02
- Review URL: <https://joomla62.klucon.cz/administrator/index.php?option=com_joomleague&view=dashboard>
- Scope: administrator application only
- Source language: `en-GB` only
- Database tests: MariaDB 11.4 and PostgreSQL 18
- Joomla target: current `6.2-dev` test build

## Purpose

This build is an architecture and information-design review, not a feature
complete release. It deliberately shows the intended administration boundary
before CRUD workflows and the final canonical domain schema are accepted.

The primary release requirements remain:

1. sport-independent behaviour driven by immutable versioned profiles;
2. lossless, auditable migration from JL 0.93, 1.5, 2.5, 3 and supported V6 alphas;
3. equivalent behaviour on MariaDB/MySQL and PostgreSQL;
4. Joomla MVC, ACL, language and visual conventions by default.

## What is real now

### Canonical persistence

Six tables are installed on both database drivers:

- sport profile identity;
- immutable sport profile version and exact JSON payload;
- local sport type derived from a profile version;
- migration batch;
- deterministic source-row migration record;
- migration issue and resolution.

The bundled JSON payload is stored exactly and verified with SHA-256. Installing
the same package repeatedly leaves 15 profiles and 15 versions. Reusing the
same semantic version with different bytes fails instead of silently changing
historical rules.

### Read-only administration

These screens read canonical database records:

- Dashboard
- Sport Profiles
- Sport Types
- Data Migration

No create, edit, delete, publish or import action is enabled in this review
build.

### Profile catalogue

Fifteen transitional profiles are bundled: basketball, bowling, chess, darts,
esports, floorball, football, futsal, ice hockey, MMA/boxing, motorsport, rugby,
running race, tennis and volleyball.

Every profile declares `schema_version: 1.0-transitional`. The label is
intentional. KSM data is preserved, but this is not the final public schema.

## What is a proposal

The following routes work and show the planned ownership boundary, but do not
yet have accepted persistence or forms:

- Competitions
- Seasons
- Projects
- Clubs
- Teams
- Persons
- Venues
- Positions and Functions
- Event Types
- Statistics
- Stages and Rounds
- Matches
- Standings

They must not be judged as finished list layouts. Review their naming,
responsibility, grouping and expected relationship to sport profiles.

## Intended domain distinctions

### Competition versus project

A competition is a reusable identity, such as a league or cup. A project is
one concrete execution in a season, bound to one immutable profile version and
allowed overrides.

### Club versus team

A club is the persistent organisation. Teams belong to clubs and can enter
multiple projects. A venue belongs to neither concept exclusively and may be
shared.

### Person versus assignment

A person is a canonical identity. Player, staff and official roles are repeated
time-bounded assignments. Transfers, injuries and suspensions must support
multiple intervals and must not overwrite history.

### Profile versus sport type

A profile is a versioned rules contract. A sport type is a local administrator
definition derived from one profile version. A project resolves profile
defaults, sport-type overrides and project overrides in that order.

## Questions for the next review

1. Is the top-level menu order suitable for repeated daily administration?
2. Should Competitions and Seasons precede Projects, or should Projects remain
   the primary workspace entry?
3. Is `Positions and Functions` clearer than one generic `Positions` label?
4. Should Stages and Rounds be one screen or separate concepts?
5. Should Standings be a project workspace screen rather than a global menu
   item?
6. Which entities require independent global lists and which belong only inside
   a project workspace?
7. Which profile fields may be overridden at sport-type and project level?
8. Should a bundled profile update create an optional upgrade operation for an
   existing project, or should projects remain permanently pinned by default?
9. Which legacy URLs must remain publicly compatible, excluding obsolete array
   query parameters?
10. Which historical source values are currently known to have ambiguous domain
    meaning and require migration review UI?

## Required schema stress tests

The final profile schema must be exercised against all of these shapes before
new match/project tables are accepted:

- football: timed score, draw, cards and shootout;
- basketball and ice hockey: no final draw, overtime result policies;
- volleyball: nested sets and win-by-margin;
- tennis: sets, games and tie-break variants;
- darts: sets, legs and checkout statistics;
- running race: lower-is-better time, categories and team aggregation;
- motorsport: classification, laps, penalties and points by position.

## Verification completed

- PHP syntax check across component and architecture tests;
- 15 JSON files parsed successfully;
- 663 `en-GB` keys loaded, with no other bundled language directory;
- missing UI/profile constants fail the architecture test;
- six equivalent foundation tables detected in both SQL drivers;
- install and repeated update succeeded on MariaDB and PostgreSQL;
- all 17 exposed administrator routes returned authenticated HTTP 200;
- route responses contained no untranslated `COM_JOOMLEAGUE_*` constants;
- desktop DOM checks found no horizontal document overflow;
- dashboard uses Joomla/Bootstrap classes and no component CSS.

## Known review limitations

Playwright Chromium 149 can load and inspect the pages, but its screenshot call
hangs in this server environment after fonts are loaded. DOM and responsive
checks completed. Screenshot generation itself remains an infrastructure issue.

At a 390 px viewport, Joomla Atum's administrator header reports document width
between 421 and 471 px because it reserves 288 px for the administration menu
next to the page title. The component content is not the source. No custom CSS
was added to mask this Joomla 6.2 alpha behaviour.

## Recommended next implementation order

1. Accept or revise the final profile schema after stress-test fixtures.
2. Define canonical person, organisation, club, team and venue identities.
3. Define time-bounded memberships and availability.
4. Define competition, season and immutable project-rules snapshot.
5. Define generic contest, participant and score-value structures.
6. Build one complete vertical slice for football and validate that the same
   runtime works for volleyball and running races without sport-code branches.
7. Implement legacy schema detectors and row-accounting migration fixtures.
8. Add CRUD only after ACL, validation and migration ownership are accepted.

## Commands

Run the architecture check:

```bash
php tests/Architecture/verify-foundation.php
```

Build the package:

```bash
./build/build-package.sh
```
