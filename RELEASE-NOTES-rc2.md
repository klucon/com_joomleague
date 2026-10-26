# JoomLeague 6.2.0 Release Candidate 2

Changes since Release Candidate 1. This is a testing release, not a stable release.

## Match Management and Reports

- Fixed assigning starters, substitutes and team staff to match lineups.
- Improved quick-score entry and correction, including final scores without a detailed breakdown.
- Added protection against overwriting changes saved by another editor.
- Corrected chronological ordering and minute formatting in match-event timelines.
- Removed calendar invitation links from completed matches.
- Improved official nominations, assignment counters and explanatory tooltips.

## Results and Migration

- Fixed conversion of results decided after extra time or penalty shootouts, including second-leg extra time.
- Included extra-time goals in the final score without double counting; kept shootout scores separate.
- Improved handling of awarded results while preserving the recorded played score.
- Flagged incomplete or contradictory imported results for review instead of publishing an invented score.
- Preserved compatible existing catalogue records and populated missing default positions on repeated imports.
- Rejected conflicting catalogue identities, ownership and sport-profile bindings explicitly instead of silently skipping them.
- Restricted default catalogue creation to verified imported sport types.
- Fixed PostgreSQL transaction handling that could leave partially imported catalogue data after an error.

## Player Profiles and Career Statistics

- Added career statistics grouped by season, competition and team, with totals separated by sport profile.
- Linked competitions to the selected person's statistics in the relevant project.
- Added a shared competition filter for appearances and statistics.
- Added match-history pagination: five matches by default, with options for ten or twenty-five.
- Reworked career history into a compact table grouped chronologically by season.
- Improved player photographs and placeholder sizing.

## Club, Team and Referee History

- Added team-specific referee-history filtering while retaining explicitly labelled club-wide totals.
- Distinguished individual opponent teams rather than combining every team from the same club.
- Improved team-scoped coaching records and chronological first/last match links.
- Added configurable team selection to club member directories.
- Improved exact team-pair rivalry records, optional club totals, date formats and competition breakdowns.
- Strengthened publication and access checks throughout historical views.

## Administration and Usability

- Improved date-based match selection and missing-result lookback on the site-club dashboard.
- Added alphabetical default ordering to the people, club and team directories.
- Added searchable selection controls to supported administrator forms.
- Improved dependent person and position selection when assigning project members.
- Clarified contextual position assignments for people participating in multiple sports.

## Country Flags

- Added the Klucon Countries component dependency and JoomLeague connector to the full package.
- Integrated country flags through the shared country-data provider, with a textual fallback when unavailable.

## Languages

- The base distribution now bundles English (en-GB) only; other languages use separate translation packages.
- Preserved existing installed translations and Joomla language overrides during updates.
- Updated Czech, German and Polish translations for the current RC2 source and resolved outstanding translation checks.
- Translation contributions: https://translate.joomleague.eu/

## Important Upgrade Notice

RC2 automatically corrects supported migrated football results using their preserved
original data. This correction can overwrite manual edits to affected results.
Make a full backup of website files and the database before updating, test on a
copy, and review installation messages and the result-repair log.

Updating does not automatically reconstruct catalogue records missing after an
earlier failed import. Inspect conflicting catalogues before reimporting.

JoomLeague 6.2 is not an in-place upgrade from JoomLeague 6.1.x or older. Use the
dedicated conversion service and a separate clean installation for legacy data.
