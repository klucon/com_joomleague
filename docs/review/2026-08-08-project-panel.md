# Project panel implementation review

## Delivered

- Canonical Project Panel addressed by one positive `project_id`.
- Shared read-only project context repository used by Panel, Rules and Templates.
- Project identity, competition, season, sport type, immutable profile, lifecycle, publication and timezone context.
- Separate implemented actions for Settings, Rules and Templates.
- Explicit non-interactive presentation of planned Teams, Rounds, Matches and Standings domains.
- Project name now opens the panel; a separate Joomla-style edit icon retains direct editing.
- Joomla core internal return handling for project edit.
- Fixed panel returns for Rule and Template Save/Close actions.
- No schema change, custom CSS, custom JavaScript or legacy URL parameters.

## Security notes

- The component administration boundary remains protected by Joomla `core.manage`.
- Editing links and toolbars require `core.edit`.
- The project edit `return` value is decoded strictly and retained only after `Uri::isInternal()` succeeds.
- Rules and Templates do not process caller-provided return URLs.

## External review

## Verification status

- PHP syntax passes for 90 source and test files; all component XML files parse successfully.
- Foundation, template resolver, project-rule validator and UUID tests pass.
- All 17 Project Panel language references have matching en-GB definitions.
- Package installation succeeded on the MariaDB and PostgreSQL Joomla 6.2 test instances.
- Both drivers returned HTTP 200 for Panel, Rules and Templates.
- Both panels rendered three implemented actions and four planned non-interactive domains.
- A valid internal return target was retained by the project edit form; an external HTTPS target was rejected and omitted.
- Close from Rules and Templates redirected to the exact originating project panel on both drivers.
- Runtime testing exposed an incorrect vendor `Uri` namespace; it was replaced with Joomla CMS `Uri`, then the complete test passed.
- Temporary projects and their competition, season and sport-type fixtures were removed.

## Next slice

Design the canonical project-participant aggregate (project teams and individual participants) before implementing Teams on the panel. This is the first runtime domain where universal sport profiles must replace football-specific assumptions.
