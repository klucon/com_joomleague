# Sport profile schema 1.1 conversion

Date: 2026-08-10

## Converted development versions

All 15 bundled profiles now publish profile version `1.2.0` with schema version `1.1.0`. The original stress set covered:

- football: numeric score and team table;
- tennis: three-level set, game and point score;
- volleyball: two-level set and point score with win-by margin;
- running race: time result and race-result standings.

The transitional top-level keys remain available to current readers. New `contest` and `match` contracts are additive during the controlled migration.

The complete set covers numeric, nested, time and decision result shapes; head-to-head and race contests; team, competitor and race standings. Event-sourced statistics use `event_codes` arrays because one statistic may be produced by multiple events, such as one-, two- and three-point basketball scores.

Duplicate JoomLeague migration aliases were removed. Darts checkout percentage is no longer incorrectly sourced from a single checkout event, and tennis set wins are explicitly linked to the set-won event.

## Development database result

The project is still pre-release, so unreferenced superseded rows were removed from both test databases. Each database contains exactly 15 active profile versions and no superseded versions. Every row reports schema `1.1.0` and profile version `1.2.0`.

Historical immutable versions will be retained only after the first official profile-contract release.

## Verification

## Follow-up

Use the canonical match contracts to design score-component persistence without adding sport-specific columns to the match table. Before the first official release, enable and test permanent immutable-version retention as a release invariant.
