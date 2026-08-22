# Standings scopes and adjustments review

## Delivered

- Sport profile schema `1.4.0` and bundled profile version `1.5.0` for all 15 profiles.
- Declarative `total`, `home`, `away` and `overall` scope execution.
- Exact decimal corrections applied before derived standings metrics.
- Canonical `standing_adjustment` schema for MariaDB/MySQL and PostgreSQL.
- Standard Joomla list/edit MVC, ACL-aware toolbar actions, CSRF handling through Joomla controllers, filtering, pagination and English/Czech language keys.
- Scope selector and adjustment manager shortcut in the standings view.
- Reset/uninstall/update-chain integration.

## Safety review

- Project, stage and participant ownership are enforced by composite foreign keys and repeated in the table validation layer.
- Scope and metric codes must be declared by the pinned project profile. Difference and ratio metrics cannot be edited directly.
- Zero corrections and corrections without a reason are rejected.
- Corrections never update match results or prior snapshots.
- Existing projects pinned to a profile without an executable calculation borrow only that contract from the active version of the same profile identity. Pre-scope calculations receive a single-scope adapter; immutable payloads are not rewritten.
- The snapshot checksum contains profile payload, scope, stage, entries, results and corrections, preserving idempotency while producing a new snapshot after a meaningful correction.
- No production or `fotbal2` deployment is part of this work.

## Verification

- All unit tests passed, including scope filtering, correction ordering and derived-metric rejection.
- Architecture verification passed with 15 profiles, 43 canonical tables on both drivers and matching `en-GB`/`cs-CZ` language key sets.
- PHP syntax validation passed for administrator and plugin sources.
- Clean package installation and Joomla Database Checker passed on temporary MariaDB and PostgreSQL installations at schema anchor `6.2.0-2026081501`.
- Repository integration passed against the deployed MariaDB (`mysqli`) and PostgreSQL (`pgsql`) test installations.
- Playwright verified the stages list and the standing-adjustment list/edit workflow on `joomla62.klucon.cz`, including an empty project-participant list and translated dynamic scope/metric options.
- The stages, rounds and matches list models now bind state values through local variables, avoiding PHP's `Only variables should be passed by reference` diagnostic. An architecture guard rejects direct `getState()` expressions in future model `bind()` calls.
- Stage standings respect `entry_selection_mode`: inherited stages use every eligible project participant, while explicit stages use only `stage_entry` assignments. Stage-specific adjustments enforce the same ownership boundary.
- Every stage row provides a Joomla-native standings action. The standings header identifies the selected stage and Close returns to the stages list.
