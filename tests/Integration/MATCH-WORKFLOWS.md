# Match workflow acceptance

Run `node tests/Integration/run-audit-coverage.mjs` from the repository root.
Use an existing dedicated TMPDIR on disk-a if the default temporary
filesystem lacks space/inodes. Requirements and isolation are documented in
AUDIT-COVERAGE.md.

## Repository cases

- Lineups: eligible membership assignment, captain/shirt snapshots, stored
  substitutions and sequence order, inactive outgoing player rejection,
  invalid removal/resequencing, foreign participant rejection, expired and
  foreign roster-member rejection, source membership removal preserving
  the historical lineup, owned lineup deletion.
- Events: unknown profile event rejection, another match's participant
  rejection, required second person validation, opponent-person rejection,
  goal/assist/system-event writes, timestamps/order, immutable identity and
  event-definition snapshots, actual event ranking, foreign deletion denied,
  owned deletion.
- Statistics: same-target upsert, percentage bounds, manual entry rejection
  for event-sourced statistics, player/participant target validation,
  historical snapshot survival after lineup deletion, exact duration storage
  in milliseconds, team/person/duration rankings, participant and project
  summaries, foreign deletion rejection and owned deletion. Fixtures cover
  football, basketball and running race.
- Officials: overlapping assignments rejected, non-official and wrong actor
  type rejected, date-based eligibility, another project's role rejected,
  project and direct non-delegated nominations, three-step referee replacement
  replay, immutable original nominations, public person history and site-club
  balance, rival history filters, duplicate assignment rejection, foreign
  deletion denied, source-role removal retaining the match snapshot and
  inactive historical nominations after removal.

These are native repository and reader tests, not substitutes for browser
form submission, HTTP controller ACL/CSRF tests or concurrent edits. R1/R4
acceptance covers separate controller ownership and atomic-save cases.

## Isolation and reporting

The four scripts verify the actual Joomla host/database/table prefix before
creating business fixtures. Only the disposable runner's jl-r1-* / r1_test /
r1_ targets are accepted. They initialize the native Joomla namespace map
and application context explicitly, including CLI request metadata.

An allowlisted child wrapper catches failures and emits only exception type
and source basename/line, not exception messages, SQL or stack arguments.
The parent requires zero exit, no runtime warning/fatal/skip and the existing
success assertion marker. Failures cannot be replaced by a partial PASS.

Before/after counts must match for every component table, with at least 50
tables required to prevent an empty coverage result. The accepted schema has
59. This checks leftover additions/deletions, not byte-for-byte preservation
of existing rows, database sequence values or every possible orphan case.
The surrounding disposable database is removed after the run regardless.

The enlarged audit has 25 mandatory native cases and six existing browser
observations per database. No production deployment, package rebuild or
publication is part of this command. Mail remains disabled.
