# Demo reset and anonymous technical statistics

## Demo reset

The package installs the guarded Joomla CLI command:

```bash
JOOMLEAGUE_ALLOW_DEMO_RESET=1 php cli/joomla.php joomleague:reset-demo-data --force
```

Both the environment flag and `--force` are mandatory. The command resets only
JoomLeague runtime tables and their identities. Bundled sport profiles, immutable
profile versions, Joomla users, extension parameters and global Joomla settings are
preserved. MariaDB/MySQL and PostgreSQL use their native foreign-key-safe truncate
paths.

This command must never be exposed as an administrator web action.

## Anonymous statistics

Transmission is disabled by default. An administrator can select `Never`, `Send
once`, or `Send once every 30 days` in the component configuration. The payload is
limited to:

- random installation UUID;
- JoomLeague version;
- Joomla version;
- PHP version;
- configured site language;
- event (`install` or `heartbeat`) and selected consent mode.

It does not contain a domain, URL, content, clubs, competitions, people, user data or
credentials. Delivery to `https://stats.klucon.cz/collect` is best-effort with a
three-second timeout. A successful timestamp is stored only after a 2xx response.
Failures do not affect the administrator interface.

## Scheduler integration

The package installs and enables the `task/joomleague` plugin and idempotently
creates an enabled `joomleague.telemetry` Scheduler task. Joomla evaluates the task
every 24 hours. `TelemetryService` remains the authoritative 30-day gate, so manual
or unusually frequent Scheduler runs cannot cause early monthly transmission.

The Quick Icon plugin performs no telemetry or network work. Existing task schedule,
state and parameters are preserved during package updates.

The JoomLeague dashboard reads the task state through the public `com_scheduler`
MVC model. It reports whether the task exists and is enabled, its last and next run,
execution and failure counts, and a failed exit code. Diagnostics do not query the
Scheduler database table directly and remain non-fatal when `com_scheduler` is not
available.
