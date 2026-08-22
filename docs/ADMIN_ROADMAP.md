# JoomLeague 6.2 administrator roadmap

This checklist tracks the remaining administrator work before migration and release preparation. A point is removed only after MariaDB, PostgreSQL and browser verification.

## Completed foundation

- Profile, sport-type and project configuration, including layered rules and templates.
- Global entities, project participants, memberships, stages and stage assignments.
- Rounds, matches, lineups, substitutions, officials, events, statistics and profile-driven results.
- Universal standings, profile-defined scopes, stage contexts and auditable adjustments.
- Stage progression topology, qualifier preview, audited idempotent execution, automatic participant assignment and executable result carry-over.
- JSON-driven schedule generation with Berger tables, race events, venue/time defaults, conflict preview and audited transactional application.
- Project preflight covering profile contracts, participants, stage assignments, schedules, officials and persisted result validity.

## Remaining points

1. **Migration administration (last functional point)**: source inventory, dry run, mapping decisions, resumable execution and audit report for JoomLeague 0.93 through 3.x.
2. **Release hardening**: full ACL, accessibility, responsive, language-key, database-parity, update-chain and clean-install audit with release packaging.

Migration intentionally remains after the canonical administration model is complete.
