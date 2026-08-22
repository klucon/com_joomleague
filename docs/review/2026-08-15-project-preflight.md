# Project preflight review

## Scope

The project panel now opens one read-only operational-readiness workspace. It evaluates canonical project data against the project's immutable sport-profile version and provides translated links to the relevant administrator workspace. It never modifies records or introduces sport-code branches.

## Checks

- Sport-profile schema and universal participant model validity.
- Published participant presence, allowed entry kinds and usable display identities.
- Stage presence and participant assignments for explicit-assignment stages.
- Round and match presence, scheduled start values and participant cardinality derived from `contest.type`.
- Availability of profile-defined project officials and match coverage. These remain warnings because profiles do not yet declare required official counts.
- Every persisted result is reconstructed and revalidated through `MatchResultRepository` and `MatchResultPayloadValidator` against the immutable profile contract.

Blocking errors determine the readiness state. Warnings remain visible without falsely preventing operation. The report is calculated on demand and stores no duplicate state.

## Verification

- Integration scenarios pass on MariaDB and PostgreSQL for an empty project, a complete four-team Berger schedule and a deliberately damaged match participant assignment.
- All unit and architecture checks pass with 15 profiles, 48 equivalent tables and 2,147 matching en-GB/cs-CZ language keys.
- Clean package installation and Joomla Database Checker pass on both drivers.
- Authenticated desktop and 390-pixel browser checks cover the project-panel entry and report without untranslated keys, PHP diagnostics or horizontal overflow.
- Test deployment backup: `/mnt/disk-b/server-backups/joomla62/20260815-170108`.
