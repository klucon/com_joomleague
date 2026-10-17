# Result Family Browser Acceptance

Run only against disposable Joomla installations:

```sh
TMPDIR=/mnt/disk-a/dev/tmp node tests/Integration/run-result-families.mjs
```

The full `run-audit-coverage.mjs` runner also requires this suite after its
native, baseline browser and football/ranking checks. Both runners validate
all 15 mandatory observations independently against `family-browser-contract.cjs`.

Firefox logs in as the disposable installer administrator. This suite does not
claim scoped-user ACL coverage; that remains in the ownership/football suites.
Each installed tennis, running_race and mma_boxing profile gets a separate
published project. The real toolbar saves the seeded result, including its
normal finalization timestamp. Reopening must preserve the expected controls.
Native form inputs reverse the scores/status/ranks, then anonymous event reports
must show the corrected result on desktop and 390px mobile without page overflow.
Screenshots accompany each public view.

For tennis, real Add Set and Remove Segment buttons add a fourth set and remove
it again without changing persisted results. This catches task-name shadowing,
null segment-code dispatch, and incorrect ordering of newly added segments.
The result editor asset must resolve to an HTTP 200 JavaScript resource.

For running, the editor and public root/child values use formatted durations,
not raw milliseconds. MMA outcomes use translated Winner/Loser labels.
Public decimal formatting must not convert stored values through floating point.

Each fixture is removed in a finally block; ten component table counts must
return to their baseline. Guarded CLI actions refuse non-disposable host,
database or prefix combinations. Mail is disabled; no production access,
package build or publication takes place.

This is not exhaustive browser acceptance of every profile/variant, walkover,
disqualification, team encounter or carom rule. Existing native service cases
also cover draft saves and rejected foreign participants; those are not counted
as browser editor cases here.
