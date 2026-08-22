# Match statistics review

## Scope

- Added the canonical `match_statistic_value` table for MariaDB/MySQL and PostgreSQL.
- Added a profile-driven repository and Joomla administrator workspace.
- Added the match-list action that opens the workspace.
- Added exact value validation, source ownership, target-scope validation and historical snapshots.
- Added reset and uninstall ownership.

## Verification

## Safety

The schema is additive. No existing table or value is removed. Match and participant deletion cascade into owned values. Lineup deletion clears only the optional assignment link and retains the person and snapshots. Manual writes require Joomla edit permission and a valid session token.
