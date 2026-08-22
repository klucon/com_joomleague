# JoomLeague 3 import validation

## Scope

Validation of the first full JL3 migration package against the Joomla 6.2 development component and the project preflight service.

## Corrections

- Legacy sport type language constants are normalized to the profile-backed display name during conversion.
- Aggregate legacy results carry an explicit `legacy_aggregate_only` marker and are accepted only by profiles that opt into that compatibility contract.
- Legacy aggregate result roots use the canonical `result` score level.
- Matches with missing or cross-project participants are excluded from the operational schedule and retained as archived migration records.
- Invalid legacy round date ranges are normalized without inventing dates.
- Standings are recalculated automatically when the current standings view is opened.
- Projects default to descending ID order in both the model and search-tools form.
- Stage codes are generated internally and no longer exposed as a required editor field.
- Official assignment wording and the stage standings icon use the component language files and Joomla icon classes.

## Result

- Imported projects: 40
- Preflight-ready projects: 40
- Blocking preflight errors: 0
- Operational matches: 3,866
- Imported final results: 3,373
- Archived matches with invalid participant assignments: 13

Referee and functionary checks remain warnings because the source dump does not contain assignments that can be migrated. They are not silently fabricated.
