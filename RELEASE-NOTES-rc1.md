# JoomLeague 6.2.0 Release Candidate 1

## Administration
- Fixed assigning and removing events and statistics for roles, including preserving selections after saving.
- Fixed script loading for dual-list selectors, role filtering and schedule autosaving.
- On mobile, first names now appear below surnames in the Persons list. Desktop retains separate columns.
- Improved the layout of wide administrator lists.
- Fixed navigation to participants in matches with a fixed number of opponents.
- Improved project-context validation when opening and saving records.

## Media and Logos
- Installation and updates automatically create the JoomLeague media directory structure without overwriting existing user images.
- Custom media directory settings apply to both native Joomla media pickers and frontend image rendering.
- Standardised fallback images for clubs, teams, people and venues across the component and related modules.
- Fixed teams inheriting their club's actual logo when their own logo is unavailable.
- Added resolution of existing images in legacy directories without requiring files to be moved.
- Fixed image URLs when using SEF routing or installing Joomla in a subdirectory.
- Strengthened validation of invalid paths and files outside the Joomla installation.

## Results and Schedule
- Fixed saving results through standard administrator toolbar buttons.
- Fixed the ordering of newly added sets and removal of result segments.
- Standardised numeric result formatting without unnecessary trailing zeros, and time formatting according to the result type.
- Fixed shootout winner selection for progression while preserving the original drawn score.
- Fixed progression handling when the winner changes or a deciding shootout is removed.
- Fixed cancelled-match handling in standings, statistics and schedule views.
- Fixed PostgreSQL team schedules incorrectly excluding some matches.

## Standings, Statistics and History
- Improved bracket dependencies on decided preceding matches.
- Fixed exclusion of restricted people and teams from relevant statistical rankings.
- Reworked club history loading so filtering, sorting and pagination occur before detailed records are loaded.
- Reduced excessive sport-profile loading in coaching history.
- Added independent pagination for a coach's detailed match history.

## Data Import and Restore
- Restricted SQL import validation to supported JoomLeague tables, columns and literal data values.
- Blocked imports containing unsupported database operations, subqueries or executable expressions.
- Fixed restore ordering for related tables and hierarchical records.
- Fixed PostgreSQL identity-sequence synchronisation after restoring data.
- Improved rollback after failed imports and handling of repeated restores.

## Permissions and Security
- Fixed project record ownership checks. Permissions are verified against the actual owner of the stored record.
- Match details and participants are saved atomically, preventing partial changes after a failed operation.
- Strengthened protection against concurrent writes and stale data.
- User-dependent calendar exports prevent storage in shared caches.
- Secured the handling of round names in bracket data.
- Improved frontend module error diagnostics without logging sensitive payloads.

## Languages and Updates
- Fixed language-file installation and updates to prevent outdated global copies from remaining in use.
- Added English and Czech translations for settings, sport categories, custom fields and results.
