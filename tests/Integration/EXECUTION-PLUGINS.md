# Execution Plugin Acceptance

Run from the repository root:

```sh
TMPDIR=/mnt/disk-a/dev/tmp node tests/Integration/run-execution-plugins.mjs
```

The runner creates fresh Joomla 6.2 RC2 instances and disposable MariaDB and
PostgreSQL databases. Mail is disabled. Helpers refuse non-disposable database
hosts, names or prefixes. No production deployment or data reset takes place.

Twelve mandatory observations per driver cover:

- Finder pagination without duplicates or gaps across entity boundaries.
- Projects, clubs, teams, people and venues represented in the actual index.
- Native Finder index writes and unique entity URLs.
- Reindexing a restricted person preserves its restricted access level.
- Explicit Finder garbage collection removes an unpublished person.
- Scheduler advertisement exposes the telemetry routine.
- The standard scheduler routine handler completes with consent set to never,
  without changing component parameters.
- An unrelated scheduler routine is ignored.
- Native Joomla CLI help discovers the reset command and its force option.
- Reset with force is rejected while reset is disabled.
- Enabled reset without force is rejected.
- Content hashes of every JoomLeague table remain unchanged by those CLI calls.

Parent-process contracts reject missing, duplicate or failed observations. CLI
checks require the expected process exit code and message, with a timeout.
Evidence is retained under `tests/artifacts/plugins-*`; disposable containers
and generated credentials are removed by the runner.

Scope boundaries: this is native adapter/routine execution, not a browser Finder
search test or cron/locking/rescheduling test. The positive destructive reset is
not invoked. Telemetry opt-in transmission is not tested or requested. These
checks do not prove instantaneous index updates on every administrative edit;
the tested removal explicitly invokes garbage collection.
