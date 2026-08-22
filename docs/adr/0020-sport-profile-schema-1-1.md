# ADR 0020: Sport profile schema 1.1

## Decision

Schema 1.1 adds explicit `contest`, `match.structure`, `match.score`, typed `standings`, and statistic source contracts. Extensible domain codes remain profile-owned strings rather than PHP enums.

Supported score shapes are numeric, nested, time, and decision results. Nested score levels are ordered data and may therefore represent volleyball points inside sets, tennis points inside games inside sets, or darts legs inside sets without a fixed depth.

## Compatibility and release boundary

Profile schema version and profile content version are separate contracts. During pre-release development, only the current bundled version of each profile is retained and test databases may remove unreferenced superseded versions. This keeps fixtures and identifiers representative of a new installation.

Immutable profile-version history becomes mandatory with the first official release of this contract. From that release onward, published payloads must never be changed or removed and projects bound to them must remain reproducible.

Transitional readers continue to work until every bundled profile has a validated 1.1 payload. Installation accepts transitional profiles during this controlled pre-release conversion, while the dedicated validator applies the complete contract to schema 1.1 payloads.

## Validation

The validator checks contest and score shape, arbitrary nested levels, standings contracts, unique catalog codes, and referential integrity from event-sourced statistics to one or more event definitions through `event_codes`.
