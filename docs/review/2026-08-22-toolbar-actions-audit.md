# Administrator toolbar action audit

Date: 2026-08-22

## Scope

- Every administrator `ToolbarHelper` declaration and its controller task.
- Standard list actions: new, edit, publish, unpublish, check-in and delete.
- Standard edit actions: apply, save, save and new, cancel and close.
- Custom actions: sport-profile synchronization, SQL export/import, project asset rebuild, schedule CSV export, schedule preview, standings recalculation, match batch modal, participant and lineup assignment controls.
- Link buttons and contextual return URLs across 38 administrator routes.
- MariaDB and PostgreSQL development installations.

## Corrected defects

1. Empty position, event and statistic lists rendered list-selection actions without any selectable row. Joomla's toolbar custom element then raised a JavaScript error while binding the missing checkbox.
2. The same empty-list guard was added consistently to every administrator list view, including context-dependent project lists. New remains available; edit, state, check-in, duplicate and delete actions are omitted until rows exist.
3. The Matches batch toolbar button did not open its Bootstrap modal because the modal web asset was not loaded. The view now explicitly loads `bootstrap.modal`.
4. Browser regressions used language-dependent login selectors and counted nested ACL tabs as top-level editor tabs. The selectors and tab scope were updated for Joomla 6.2.
5. The installed development copy of `script.php` had drifted from the source namespace used by the standings contract validator. Both database test installations were synchronized with the source file.

## Runtime verification

- Read-only crawl: 38 administrator routes, HTTP 200, no PHP warning/fatal output.
- SQL table export produced a downloadable `.sql` file containing structure and data.
- SQL import accepted that export and safely skipped the existing table and 124 duplicate rows.
- Project schedule export produced a downloadable `.csv` file.
- Sport-profile synchronization, project asset rebuild, schedule preview and standings recalculation completed without runtime errors.
- Match batch modal opened through its toolbar button after the web-asset correction.
- List edit navigation passed for clubs, competitions, persons, projects, seasons, sport types, teams, venues, stages, rounds, matches and project entries.
- Project duplication created a complete copy, redirected to its editor and the test copy was then deleted successfully.
- A temporary club exercised unpublish, publish, check-in and confirmed delete; cleanup completed.
- Club, team, person and venue create/delete browser workflows passed.
- Match participant and lineup custom actions returned safely when no row was selected.

## Automated verification

- `tests/Browser/verify-toolbar-actions.cjs` added as a permanent regression.
- Architecture verification passed: 15 profiles, 48 tables on both database drivers and 2482 en-GB keys.
- All 15 unit verification scripts passed.
- Administrator tools integration passed on MariaDB and PostgreSQL.
- Joomla 6.2 stack verification passed.
- Suite package rebuilt and ZIP integrity verified.

## Test-suite maintenance still outside this audit

Some older browser fixtures still try to fill the now automatically generated hidden stage `code` field, rely on obsolete imported participant names, or assert an outdated mobile pagination width. These failures do not indicate broken toolbar dispatch, but those scenarios should be updated before treating the complete legacy browser directory as a single green gate.
