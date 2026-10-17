# Match Save Atomicity

Run `node tests/Integration/run-match-atomicity.mjs` from the repository root.

The shared acceptance runner installs the current component into fresh Joomla
6.2 RC2 instances backed by disposable MariaDB and PostgreSQL databases. Actual
configuration must match the generated `jl-r1-*` database host, `r1_test`
database and `r1_` prefix before fixture writes are allowed. No existing local
or production installation is used, and mail is disabled.

## Native Cases

- Invalid/missing/duplicate/foreign participants on existing and new matches.
- Invalid dates, incomplete date/time, attendance and parent status.
- A trigger rejects slot 2 after the old participants were deleted and slot 1
  inserted: original parent and participants must survive unchanged, and failed
  creation must leave no orphan match or stale model ID.
- Transaction nesting, the original connection and existing query monitor are
  preserved after rejected saves.
- Valid nested edit and creation remain subject to the caller's rollback.
- An injected SQL error preserves earlier caller writes, including PostgreSQL.
- A second connection changes participant slots after a caller established its
  snapshot: the next save must read current participants, not that old snapshot.
- Competition data committed after that snapshot must still prevent swaps.
- Locked swaps preserve the parent/result; unchanged participants remain editable.
- Valid standalone edit/swap persists both sides together.
- Direct MatchScheduleEditor calls also roll back late failures.
- Existing query monitor events are forwarded and the monitor restored.

The injected trigger/function exists only in disposable databases and is
explicitly removed after its case. PostgreSQL's injected exception is identified
by SQLSTATE `P0001`; recovery commands can replace the textual diagnostic in the
PDO driver's error information.

## HTTP Cases

Firefox authenticates a real restricted backend editor and loads native Joomla
forms/tokens. HTTP cases cover invalid edit/create, injected late edit/create
failure, locked swap, missing CSRF, successful edit/create, and simultaneous
saves from independent sessions. The final parent and both participant slots
must match one complete submitted payload, never a mixture.

## Source Boundaries

MatchModel owns the parent/schedule transaction; MatchScheduleEditor also owns
a savepoint-aware boundary for direct callers. Both use the existing project
then match lock order used by result writers. Participant and competition-data
checks use locking reads to avoid stale repeatable-read snapshots.

PostgresqlStatementGuard is attached only for these save scopes, not installed
globally. Statement savepoints recover an aborted PostgreSQL statement before
the PDO driver's connection probe can mistake it for disconnection and discard
the caller transaction. Outer operation savepoints still roll back all changes.
It preserves the existing monitor and leaves Joomla core/vendor files untouched.

Supplemental checks:

```sh
php tests/Unit/verify-postgresql-statement-guard.php
php tests/Unit/verify-schedule-participant-lock.php
php tests/Unit/audit-security-boundaries.php
bash tests/Architecture/verify-release.sh
```

Artifacts are written under `tests/artifacts/atomicity-<run-id>/`. This closes
the R4 database write boundary, not all other RC acceptance work or external
side effects of arbitrary third-party plugins.
