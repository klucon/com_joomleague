# JSON schedule generation review

## Scope

Administrator roadmap schedule generation is complete for static round-robin and repeatable race formats. Runtime behavior is selected from the sport profile contest contract and versioned JSON resources, never from a sport code.

## Delivered behavior

- Berger first-leg and mirrored return-leg tables cover every participant count from 2 through 30, including byes for odd counts.
- A repeatable mass-start race template covers profile-defined race contests.
- Single/double elimination, group/playoff and Swiss formats are included as JSON contracts and shown in the catalog, but remain disabled until generic progressive resolvers are implemented.
- The administrator selects local start date/time, round and match intervals, numbering, return legs, home venues and publication state.
- Preview detects participant and venue overlaps against both generated and existing matches, missing venues, and project/stage date overflow.
- Apply is transactional, creates canonical rounds/matches/participants, records immutable generation metadata and is idempotent by deterministic checksum.
- Blocking conflicts require an explicit administrator override.

## Verification

- All unit and architecture tests pass: 15 profiles, 48 equivalent MariaDB/PostgreSQL tables and 2,089 matching en-GB/cs-CZ keys.
- Berger verification covers unique pairs, one appearance per round, byes and mirrored return fixtures for every size from 2 through 30.
- Clean package installation and Joomla Database Checker pass on MariaDB and PostgreSQL at schema anchor `6.2.0-2026081504`.
- Authenticated browser verification passes without PHP notices, untranslated keys or horizontal overflow.
- Test deployment backup: `/mnt/disk-b/server-backups/joomla62/20260815-164141`.
