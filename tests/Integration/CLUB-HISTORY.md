# Bounded Club History Acceptance

Run `node tests/Integration/run-club-history.mjs` from the repository root.

The runner installs the current component in disposable Joomla 6.2 RC2 instances
on MariaDB and PostgreSQL. Database host, name and prefix must match the isolated
fixture before writes are allowed. Both CLI and HTTP use a 128 MB PHP memory
limit; no existing local installation or production data is used. Mail is off.

## Fixture And Native Checks

Each database contains 1,205 people, 60 completed scored matches, 72,300 lineup
rows, memberships, coach roles from the actual sport profile and scoring events.
This reproduces the shape that previously repeated large profile payloads per
lineup row. Profiles remain immutable: lower-score-wins testing creates a new
fixture version and restores the original project pin afterwards.

Checks cover:

- Default 25-person page and SQL aggregate totals across the entire history.
- Correct appearances, coach wins/draws/losses and decimal score totals.
- Metric ordering before pagination and counts from event metadata snapshots.
- Final page/oversized offset handling and the existing 1,000-person All cap.
- Independent 25-match pagination for a selected coach; no detail list is
  hydrated for every person on a directory page.
- Person type, project, season, project-type and case-insensitive search filters.
- Literal search characters, SQL-like input and rejected unknown sort/limit.
- Empty nicknames and malformed metric metadata.
- Private projects/people/opponents, including metadata and selected details.
- Ambiguous multi-opponent matches without inflating appearance counts.
- Lower-score-wins profiles, rank precedence and absent numeric results.

## Browser Checks

Firefox visits public people/staff views at 1440px and 390px widths. Checks
include row counts, search/sort, coach detail pages, actual next-page navigation,
HTTP status, JavaScript errors and visible PHP/SQL/untranslated-constant errors.
Screenshots and HTTP observations are retained in the run artifact directory.

## Loading Boundary

History joins are aggregated on the database server, then the selected person
page is fetched. Only those people receive distinct membership/project metadata
and aggregated event counts. Profile payloads are not repeated per lineup.
Visible filter choices and metric definitions remain club-wide; their size
depends on distinct definitions/projects, not the raw number of historical
appearances. SQL ordering uses the configured database collation with a stable
person-ID tie-breaker.

The fix bounds application-side historical row hydration; it does not promise
constant database work for arbitrarily large datasets or arbitrarily many
distinct profile/metric definitions. No memory-limit increase is part of it.

Additional checks:

```sh
php tests/Unit/verify-club-history-bounds.php
php tests/Unit/verify-club-history-outcomes.php
php tests/Unit/verify-club-history-sorting.php
php tests/Unit/audit-security-boundaries.php
bash tests/Architecture/verify-release.sh
```
