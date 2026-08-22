# ADR 0013: Profile-driven project entries

## Status

Accepted for the Joomla 6.2 development foundation on 2026-08-08.

## Context

JoomLeague must represent team sports, individual contests and group entries without restoring the football-specific `project_team` aggregate as its canonical model. At the same time, migration from JoomLeague 0.93 through 3.x must retain source identities and values that do not belong in the new domain.

## Decision

Each immutable sport profile version declares an `entry_model` with allowed entry kinds, a default entry kind and supported member person types.

The canonical identity and project-entry tables are:

- `club`: an organisation that may own multiple teams;
- `team`: a reusable team identity optionally owned by a club;
- `person`: a reusable person identity, independent of project membership;
- `project_entry`: a team, person or named group entered in one project;
- `project_entry_member`: a time-bounded person membership in an entry.

Database constraints enforce the entry discriminator, foreign keys and date chronology. Application services must additionally validate entry kinds and member person types against the immutable profile version pinned by the project.

Legacy starting points, forced standings values, availability records, divisions and match line-ups do not belong in these tables. They will use dedicated aggregates. Unknown legacy values remain available through migration provenance records until a canonical destination exists.

## Consequences

- A club can own an A team and B team without duplicating the club or venue.
- Individual and team sports share one project-entry API.
- A person can leave and return because memberships are intervals, not one-time flags.
- Profile upgrades do not silently change existing projects.
- `ProjectEntryRepository` applies profile rules before writes. ACL and CSRF remain controller responsibilities and no write controller is exposed yet.
