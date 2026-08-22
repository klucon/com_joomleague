# Project stage foundation

Date: 2026-08-10

## Scope

- Added the project-scoped `#__joomleague_project_stage` table for MariaDB and PostgreSQL.
- Added Joomla list and edit MVC views, toolbar actions, filters, pagination, publishing, check-in and deletion.
- Added nested parent stages with project ownership and cycle validation.
- Added an open `stage_type` code instead of a football-specific type enumeration.
- Linked Stages from the project panel.
- Added stage data to demo-data reset and uninstall handling.

The stage editor uses Joomla tabs for Details, Description and Publishing. The Description editor occupies its own full-width row. No custom CSS or visible hardcoded UI text was added.

## Database

The update anchor is `6.2.0-2026081001.sql`. This is intentionally newer than the existing ten-digit `6.2.0-2026080808` anchor. PostgreSQL indexes use Joomla Database Checker's `/** CAN FAIL **/` convention instead of `IF NOT EXISTS`.

Clean package installation and Database Checker passed on both MariaDB and PostgreSQL. The foundation verifier reports 24 tables on both drivers.

## Verification

- PHP syntax checks passed.
- XML validation passed with `xmllint`.
- Foundation architecture verification passed: 15 profiles, 24 tables, 1502 en-GB keys, 18 menu views, 6 template definitions and 124 project-rule fields.
- Browser CRUD verification passed for parent creation, child creation, parent selection, editing and deletion.
- A clean package install passed on both supported database drivers.
- Persistent MariaDB and PostgreSQL Joomla 6.2 test installations report JoomLeague database version `6.2.0-2026081001` with no component database problems.

Backup before persistent deployment:

`/mnt/disk-b/server-backups/joomla62/20260810-211233`

## Deliberate boundaries

Stages currently define competition structure only. Stage-specific participant assignment, rounds, matches and advancement rules are not coupled to this first schema. This keeps the foundation universal and allows simple projects to inherit all project participants while grouped competitions can later define explicit stage membership.

## Recommended next step

Add stage-entry assignment with inheritance from project participants. After that contract is stable, add rounds owned by a stage so league phases, groups and knockout stages share one scheduling foundation.
