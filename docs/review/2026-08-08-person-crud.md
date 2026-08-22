# Person administration CRUD

Date: 2026-08-08

## Implemented

- Replaced the Persons placeholder with Joomla administrator list and edit views.
- Added search, publication filtering, name sorting and pagination.
- Added create, edit, publish, unpublish, check-in and delete actions.
- Added Details, Biography, Media, Description and Publishing tabs.
- Added an optional Joomla core Contact picker without custom selection code.
- Added first name, last name, nickname, country, lifecycle dates, profile picture and external migration identifier.
- Added project-entry and roster-assignment counts to the list.
- Prevented deletion while a person is an individual project entry or a roster member.
- Marked Persons as available in the central administration domain catalogue.
- Kept the list responsive with Joomla/Bootstrap utilities and no custom CSS.

## Domain rules

- A canonical person does not directly carry a player, staff or official role.
- Roles belong to dated project-entry membership assignments defined by the sport profile.
- At least one of first name or last name is required, preserving support for mononymous persons.
- Empty contact, country, dates, picture and external identifiers are normalized to `NULL`.
- Death date cannot precede birth date.

## Verification

- PHP syntax, XML, language and architecture checks pass.
- The suite reports 15 profiles, 17 tables for both database drivers and 1170 `en-GB` keys.
- The package installs on Joomla 6.2 test instances backed by MariaDB and PostgreSQL.
- Playwright validates the person list, five-tab editor and core Contact picker on desktop and mobile.
- A CRUD smoke test creates and deletes a temporary person without a Joomla Contact on both database drivers.

## Review limitation