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

Eight additional mandatory Firefox observations per driver exercise real Joomla
Finder search pages: public results at desktop/mobile widths, restricted and
unpublished records excluded for guests, restricted results available after a
real frontend login, unpublished records still excluded after login, and public
and restricted result links opened successfully. They use separately created
synthetic people and actual indexed terms, not mocked search responses.
Each query has a positive public or authenticated control; results also reject
runtime errors, untranslated component/plugin keys and page overflow.
The parent independently verifies the eight-case contract and preserves
`*-finder-http.json` reports and screenshots.

Evidence (2026-10-21): `tests/artifacts/plugins-mvhthjed/` passed all twelve
native/CLI and eight browser cases on each database (40 observations total).
Generated credentials and disposable containers/network were removed. No
production changes or package publication occurred.

Ten additional mandatory Scheduler observations per driver create a real task
through Joomla's Task model, validate the future interval, reject a task that
is not due, acquire a native lock, reject acquisition from both the same process
and a second PHP process, run the real JoomLeague routine, verify counters and
lock release, verify a five-minute next execution, reject immediate repetition
and disabled tasks, and execute a second due run. Component parameters remain
byte-identical with telemetry consent set to `never`. Due timestamps are moved
only for the owned synthetic task in the guarded disposable database.
The independent parent requires all ten observations; the helper has a
60-second timeout and the contender has a 15-second timeout.

Evidence (2026-10-21): `tests/artifacts/plugins-mvhug1p5/` passed all thirty
native/CLI/Scheduler/browser observations on each database (60 total).
The Scheduler report is `*-scheduler.json`. Contract negative tests reject
missing, duplicate, skipped, failed and empty observations. No product code or
production database was changed. All disposable containers were stopped.

Scope boundaries: browser acceptance covers search and link visibility, not
automatic reindexing on every administrative edit. Scheduler tests target the
owned task using native scheduling eligibility checks and `allowConcurrent=false`;
they do not certify CLI forced-concurrent execution, OS cron, Web Cron,
wall-clock trigger delivery, crash recovery or manual/cron-expression schedules.
The positive destructive CLI reset is now invoked only in these guarded
disposable installations, as described below. Telemetry opt-in transmission is
not tested or requested. These
checks do not prove instantaneous index updates on every administrative edit;
the native tested removal explicitly invokes garbage collection. Guest direct
access to a known restricted detail URL is not an assertion of this focused suite.

## Positive Reset Acceptance (2026-10-21)

Eighteen additional required observations per database cover the reset inventory,
an actual external foreign key and refusal before mutations, the real forced CLI
reset, empty runtime tables, removal of old project ACL assets and JoomLeague
Finder links, preserved profile/category/variant tables, preserved Joomla data,
automatic disabling of reset permission, unrelated search-result preservation,
active database constraints, valid nested-set asset structure, consistent Finder
term counts/mappings, restarted identity metadata, generation of all fifteen demo
profiles from project ID 1, and a second successful reset with preserved data.

The foreign reference uses `ON DELETE CASCADE` deliberately. After the refusal,
raw hashes of all original tables are unchanged, and the external fixture still
exists. Only the test removes that synthetic reference before positive reset.
Similar-looking extension/asset names are positive controls against broad `LIKE`
deletion. Finder setup completes the native indexing/optimization batch and
requires a healthy term-count baseline before testing reset.

Core-data comparison excludes Finder's intentionally maintained index, removes
only exact numeric JoomLeague project assets, normalizes nested-set coordinates
and allows only `allow_full_data_reset` to change in component settings. A
separate assertion validates the whole remaining asset tree. PostgreSQL binary
streams are compared by encoded content, not resource handles.

The tests found that reset retained old ACL assets and indexed URLs. The service
now removes those through native Joomla APIs, matches the exact component option
and exact project-asset namespace, checks external foreign references before any
cleanup and no longer uses PostgreSQL `TRUNCATE ... CASCADE`. New failure messages
are translated through component language keys. The older destructive lifecycle
helper now also refuses non-disposable targets.

Evidence: `tests/artifacts/plugins-mvhvo8om/` passed all 48 required observations
per database (96 total). Reset details are in `*-reset.json` and CLI transcripts.
All test containers/network and generated database credentials were removed.
No production deployment or package publication occurred.

Additional checks: reset/Scheduler/report contract units, PHP syntax checks,
`audit-security-boundaries.php` and `bash tests/Architecture/verify-release.sh`
passed. The broader `verify-foundation.php` remains blocked by its existing
two-language-directory requirement while source also contains de-DE/pl-PL.
That separate language/distribution gate is not declared passing or weakened.

Browser reset ACL/CSRF/confirmation submissions are now covered below. This does
not certify interrupted MySQL DDL recovery, concurrent third-party schema changes,
or repair of an already-corrupted Finder index. No all-or-nothing MySQL reset is
claimed.

## Browser Reset Security (2026-10-21)

Eighteen Firefox cases per database exercise the real administrator route with
separate guest, scoped editor and super-administrator sessions. A fresh synthetic
football data set includes actual project ACL assets and indexed URLs. The
feature starts disabled; the super administrator enables it using the native
Joomla Options form, not a direct database change.

Cases cover the disabled form and forged POST, enabling Options, guest redirect
to login, editor denial for both view and POST with its own valid CSRF token,
missing/invalid CSRF, GET with token in the query, empty/wrong-case/HTML-decorated/
space-decorated/array confirmations, successful reset through the actual form,
preservation of core/profile data and settings, automatic re-disabling and a
rejected repeat POST. Every rejected mutation compares before/after database
hashes including runtime data, assets, Finder index and component settings.
The positive form submission uses the same admin session/token, proving the
negative cases are not merely broken authentication or an inoperative endpoint.

The HTML-decorated confirmation reproduced a defect: `getString()` stripped the
tags and allowed reset. The controller now reads only the raw submitted value,
requires a string and compares it literally with `hash_equals`. The raw value is
never rendered or written to SQL. A focused PHP unit test rejects fifteen malformed
values, accepts the exact phrase and checks CSRF/ACL/disabled guard ordering.

Evidence: `tests/artifacts/plugins-mvhvxyk0/` passed all 66 required observations
per database (132 total), including the earlier native/CLI/Scheduler/Finder cases.
`*-reset-http.json` records browser observations; `*-reset-before.png` and
`*-reset-after.png` show the real UI. The parent independently validates all
eighteen browser cases, with a 180-second process deadline. The contract unit
rejects missing, duplicated, skipped, failed, untranslated and empty evidence.

PHP syntax, focused PHP/JS units, security-boundary tests and the focused release
contract passed. The broader foundation audit still stops at its existing
language-directory expectation; it is not reported as passing. Disposable
containers/network and generated credentials were removed. No production change,
package publication or deployment occurred. This does not certify interrupted DDL
recovery or remotely downloaded package updates.
