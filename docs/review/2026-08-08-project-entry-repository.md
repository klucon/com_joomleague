# Project entry repository and administrator view

Date: 2026-08-08

## Delivered

- Added `ProjectEntryRepository` as the profile-aware write and read boundary.
- Entry creation validates the project profile's allowed team/person/group kinds.
- Member creation validates roster support, person type, profile position, date format and chronology.
- Added the read-only Project Participants administrator view.
- Activated Project Participants in the Project Panel.
- Added only en-GB interface strings and Joomla core presentation classes.

## Verification

- Repository integration test passed on Joomla's `mysqli` and `pgsql` drivers.
- Valid football team and goalkeeper membership were persisted inside a test transaction.
- Individual football entry, unknown member type, staff role used as a player and reversed dates were rejected.
- The administrator view returned HTTP 200 and resolved a temporary team entry.
- The HTTP fixture and all related identities were removed after verification.

## Security boundary

The repository validates domain rules but does not make authorisation decisions. Future write controllers must require component ACL, a valid Joomla session token and integer-filtered identifiers before calling it. No write action is exposed by this slice.

## Review limitation

## Next slice

Implement canonical Club, Team and Person CRUD first. Project-entry assignment can then reference managed identities instead of accepting ad-hoc names or untrusted identifiers.
