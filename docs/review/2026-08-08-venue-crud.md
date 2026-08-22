# Venue administration review

Date: 2026-08-08

## Scope

- Canonical Venue schema for MariaDB and PostgreSQL.
- Joomla administrator list, filters, editor, ACL-aware toolbar and state actions.
- Details, Location, Facilities, Media, Description and Publishing tabs.
- Optional owner club, coordinate validation, system time-zone fallback and one canonical venue picture.
- JL3 playground migration contract.

## Safety

The schema is additive. No existing table or data is changed. The optional owner-club foreign key uses `ON DELETE SET NULL`, so deleting an otherwise unused club does not delete a venue. Coordinates are rejected unless both values are supplied and are within geographic ranges.

## Verification

- PHP syntax, XML, language-key and architecture checks pass.
- The suite reports 15 profiles, 18 matching canonical tables and 1221 `en-GB` keys.
- The package installs successfully on the Joomla 6.2 MariaDB and PostgreSQL development instances.
- Both databases contain the full Venue schema and an owner-club foreign key using `ON DELETE SET NULL`.
- Playwright creates and deletes a populated venue on both drivers, verifies all six tabs and checks the Venue list and editor for mobile horizontal overflow.
- The broader layout suite identified a pre-existing mobile overflow in the Persons list. It is outside this Venue change and remains a tracked follow-up.
