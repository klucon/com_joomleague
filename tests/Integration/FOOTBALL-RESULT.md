# Football result browser acceptance

Run `node tests/Integration/run-audit-coverage.mjs`. The audit runner executes
this suite after its native cases and basic browser observations, on each
fresh MariaDB/PostgreSQL Joomla 6.2 RC2 installation. See AUDIT-COVERAGE.md
for environment requirements and temporary filesystem capacity.

The suite uses a project-scoped editor, not a superuser, and real native
result fields and the Joomla Apply toolbar button. Its test account's group
can view the administrator toolbar module. This view-level membership does
not grant mutation permissions on the other project.

## Eighteen required cases

- Save final 2:1, with periods 1:0 and 1:1, through the rendered form.
- Reopen the form and verify exact input strings without database-scale zeroes.
- Read actual 2 and 1 score elements on the public event report. A note
  containing an image/onerror payload must render as literal text, without
  creating the image element or executing its handler.
- Correct the result to 0:1 and confirm the public score updates.
- Read the automatically refreshed standings, verify changed metrics and
  a loss/win for the appropriate entries. The probe does not recalculate.
- Reject a nonnumeric result without changing persisted data.
- Reject a foreign match ID even with a forged allowed project ID.
- Reject a missing CSRF token without changing persisted data.
- Save a draft and verify both entries have zero counted matches in the
  automatically refreshed standings.
- Save a 3:0 forfeit with corresponding period values.
- Reject unsupported result status `cancelled` without changing the database.
- Cancel the scheduled match through its own native edit form. Its final
  result and score rows remain unchanged, but the automatic standings and
  project completed count exclude it. The programme retains the cancelled
  item without an effective completed score; next-event/programme modules
  must return their legitimate EMPTY state, and Teamplan must not advertise
  it as the next match. The public event report displays the cancelled state.
- Restore the scheduled match and verify the retained result counts again,
  without rewriting the historical result/score rows.
- Save an extra-time 2:1 after regulation periods total 1:1, reopen the form
  and verify both extra-time parts and active phase flags.
- Save a shootout 4:3 while retaining the ordinary result 1:1. The public
  report must render the root score and shootout separately.
- Correct that shootout to 3:4 without changing the 1:1 root. The tested
  football league contract counts the drawn ordinary score in standings;
  this does not claim that every competition uses those point rules.
- Reject an aggregate root inconsistent with period/extra-time values,
  without modifying the persisted snapshot.
- Disable both optional phases, save a normal 2:0, verify old extra-time and
  shootout segments are gone and the league table reflects the new win/loss.

Before/after rejected-write comparisons cover matches, participants, results,
score segments/values, standings freshness and current snapshot pointers.
They do not claim byte-for-byte comparison of every Joomla table.

## Scope and evidence

The runner independently checks the source-controlled required case list in
football-result-contract.cjs. Missing/unfinished cases fail, even if earlier
cases passed. Evidence is driver-specific football-http.json and editor/public
screenshots in the runner's artifact directory. The football editor is tested
at desktop width; this is not a mobile form audit or all-result-family audit.

The football profile's result statuses are draft/in_progress/final. Cancelling
the scheduled match is a separate workflow, now tested independently of the
unsupported result-status negative test. The extra-time/shootout cases cover
form storage, editing, removal, aggregate validation and public scores. They
originally did not cover automatic selection/progression of the shootout winner, all
conditional-phase legality constraints or every configurable point rule.
Other sport result families and cancellation-specific populated person/event
ranking fixtures remain separate coverage tasks.

Business writes target only disposable fixtures. No production access, mail
delivery, package generation or deployment is performed.

The result editor template now uses the existing DecimalDisplayFormatter for
numeric input presentation. This removes trailing decimal zeroes without
rounding, float conversion or changes to persisted results. Unit coverage
includes nine decimal places and integer values above native integer range.

### Shootout qualification follow-up (2026-10-17)

The qualification follow-up expanded the football suite to 21 cases. Three cases call the
real stage progression preview/apply service after native HTTP result edits:
4:3 selects the first entry, correction to 3:4 replaces it with the second,
and removing the decider while keeping 1:1 clears the automatic assignment.
The fixture uses a separate target stage; existing all-entry assignments are
not overwritten. League standings still count the root 1:1 as a draw.
These are service-level progression checks, not clicks on the transition UI.
The bracket uses the same outcome resolver. Populated cancellation-specific
rankings remain open.

### Three-round bracket browser follow-up (2026-10-17)

The suite now requires 26 cases. Anonymous Firefox checks a three-round bracket
after the 4:3 shootout, its reversal to 3:4, and removal of the decider. Only the
qualified participant is named in the next round; later unresolved slots stay
pending. A deliberately stale final result in round two must not qualify a
participant whose round-one qualification was invalidated.

Desktop and 390px mobile captures accompany navigation/highlight checks.
A round title containing a closing script tag is displayed as literal text;
no injected JavaScript may execute. This exposed and repaired unsafe script
embedding of round labels. Scores now use DecimalDisplayFormatter instead of
local zero trimming. These checks cover public bracket resolution, not automatic
rewriting of stored participants or replaying an entire competition to its final.

### Populated rankings and additional families (2026-10-17)

The suite now requires 33 observations. Four ranking cases insert person and
participant events/statistics, assert non-empty totals, hide the referenced
person/team from public access, cancel the match through the native form, and
restore it. Hidden targets and cancelled contributions must disappear. Stored
event/statistic rows must remain unchanged and restored totals must match.
This exposed a missing team/person access check in participant event rankings.

Three additional native service cases cover installed tennis, running_race,
and mma_boxing profiles. They compose payloads using MatchResultFormPayloadBuilder,
persist/reopen through MatchResultRepository, reverse the winner, reject a
foreign participant without changing the stored result, and save a draft.
Tennis checks nested sets/games; running checks millisecond precision and
lower-is-better; decisions check status/rank winners. Their temporary projects
are deleted and counts in ten component tables must return to baseline.
These three cases are service integration tests, not browser editor acceptance.
