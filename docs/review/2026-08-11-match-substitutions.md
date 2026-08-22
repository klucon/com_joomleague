# Match substitutions review

## Scope

- Added cross-driver `match_lineup_change` persistence over stable lineup snapshots.
- Added profile-gated substitution creation, ordered active-lineup validation, re-entry support and safe removal.
- Replaced the placeholder administration tab with native Joomla form controls and translated feedback.
- Extended the destructive demo reset and uninstall paths for the new child table.

## Invariants

- Both players belong to the same match participant and are assigned as players.
- The outgoing player is active and the incoming player inactive at each sequence step.
- Phase codes originate from the immutable project profile.
- Profile substitution support and the configured default limit are enforced without sport-code branches.
- Removing an earlier change is rejected when it would invalidate later changes.

## Verification