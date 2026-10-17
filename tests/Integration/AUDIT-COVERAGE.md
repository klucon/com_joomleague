# Mandatory audit coverage

Run from the repository root:

```sh
node tests/Integration/run-audit-coverage.mjs
node tests/Unit/verify-audit-process.cjs
node tests/Unit/verify-audit-result.cjs
php tests/Unit/verify-module-error-logging.php
```

The acceptance runner uses the existing ownership runner's isolated MariaDB
and PostgreSQL environments, fresh Joomla 6.2 RC2, randomly generated scoped
identities, mail disabled and localhost-only HTTP ports. It does not use an
existing Joomla database. Native writes are guarded by the actual temporary
database host, database name and table prefix. Modules come from source and
are installed through Joomla's native installer.

Requirements: Docker, the local Joomla RC2 package/runtime image and the
existing Playwright Firefox installation. Webroots are preserved for
diagnosis; generated credential files and test containers/networks are
removed. Ensure sufficient space and free inodes. To keep new webroots off
a full temporary filesystem, set TMPDIR to an existing dedicated directory
on disk-a before invoking the runner. Never delete unrelated temporary files.

## Required cases

`audit-contract.cjs` defines the independent expected native and HTTP case
sets. The parent validates those contracts rather than trusting a child
report's declared requirements or exit code alone.

The 25 native cases exercise installed namespaces, explicit non-empty
fixtures, participant template overrides, programme content/scope/filtering,
stage preview/application/idempotent replay, birthday content, legitimate
empty birthdays, injected birthday/template failures, the original programme
and participant checks, Teamplan's strict participant identity comparison,
calendar/programme/standings module checks and controller ownership probes.
The standings fixture is explicitly recalculated and must contain two rows.
Four additional workflow cases run the actual lineup/substitution, event,
statistic and official-assignment repositories with their own records and
negative checks. A fifth compares component table counts before/after those
four workflows (59 tables in the accepted schema). See MATCH-WORKFLOWS.md
for exact scope and limitations.

Six browser observations require real semantic content: participant and
programme pages at 1440/390 pixels, plus transition preview/application as a
restricted editor. The transition POST uses the native Joomla form token.
Checks reject untranslated keys, runtime errors, unexpected HTTP statuses,
JavaScript errors and horizontal overflow. Screenshots, public synthetic
fixture text, native case reports and HTTP reports go to tests/artifacts.

After those six observations, the runner also requires thirty-three result
and ranking observations, followed by fifteen result-family browser observations.
See `RESULT-FAMILIES.md` for the editor-specific scope and isolated runner.
The full runner also requires nine native content/quickicon observations and
seven admin asset/link browser observations; see `ASSETS-AND-PLUGINS.md`.
Full ZIP installation/update has a separate guarded runner documented in
`PACKAGE-ACCEPTANCE.md`.
The first group is independently validated against football-result-contract.cjs.
These exercise the native Apply button, persisted and public scores, automatic
standings refresh, draft/forfeit, match cancellation/restoration, extra-time/shootout
storage/correction/removal and rejected writes. Scope and exclusions are
documented in FOOTBALL-RESULT.md.

## Failure handling

Every required native case starts as NOT_RUN. Only a completed callback with
a positive integer evidence count can become PASS. Early shutdown writes
an unfinished failure report, including when PHP exhausts memory. Reports
omit arbitrary exception messages and arguments; only exception type,
source basename/line and fixed test assertion labels are retained.

The process classifier rejects nonzero exits, signals, timeouts, runtime
failure output and SKIP/FAIL output. It also rejects unfinished, missing,
duplicate, unexpected or zero-evidence cases even when the process exits
zero. Browser observations are independently checked against their required
set; missing observations are failures, not optional skips.

Adversarial tests execute actual PHP subprocesses with omitted cases,
exceptions, early successful exit and a real allocation above memory_limit.
They also assert that a synthetic sensitive exception payload never reaches
the report. These deliberate failures are negative tests, not product faults.

## Scope

R6 closes the identified masking and mandatory-fixture gaps. It does not
claim exhaustive coverage of all Joomla workflows, all plugins, every public
link, package upgrades or final release readiness. Those remain separate
acceptance gates in the RC review document. No production deployment occurs.
