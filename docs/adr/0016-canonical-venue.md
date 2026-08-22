# ADR 0016: Canonical venue

Date: 2026-08-08

## Status

Accepted

## Context

Legacy JoomLeague stores physical grounds as playground records. JoomLeague 6.2 needs a sport-neutral place that can be shared by clubs, teams, matches, races and training schedules without assuming that every place is a football stadium.

## Decision

The canonical entity is named Venue.

- A venue represents a physical place and contains neutral location, capacity, media and publishing data.
- `owner_club_id` is optional and records ownership or operation only. It does not restrict which projects, clubs or teams may use the venue.
- Coordinates are stored as decimal latitude and longitude and must be provided as a pair.
- A nullable venue time zone means that Joomla system and project scheduling defaults apply.
- Virtual competition platforms are not represented as venues. They belong to a future contest-delivery model.
- Sport-specific surface, lane, court or track configuration is not embedded in the base venue table. Such capabilities will be attached through profile-driven extensions when required.

## Migration

JL3 `playground` records map to `venue`. `club_id` maps to optional `owner_club_id`, `max_visitors` maps to `capacity`, `zipcode` maps to `postal_code`, `notes` maps to `description`, and `picture` maps directly to `picture`. The legacy `extended` nickname value maps to `nickname` when present.

## Consequences

Future match and training entities reference a venue rather than copying address data. A venue can be reused independently of its optional owner club. MariaDB and PostgreSQL enforce coordinate ranges and PostgreSQL also enforces nonnegative capacity; MariaDB uses an unsigned capacity column.
