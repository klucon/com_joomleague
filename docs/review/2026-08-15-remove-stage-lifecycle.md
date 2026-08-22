# Remove the stage lifecycle field

## Decision

Project stages no longer persist a manually maintained lifecycle state. Their
publication remains a Joomla core state, while schedule and completion facts
are derived from dates, rounds, matches and results.

This change intentionally does not remove lifecycle/status fields belonging to
project entries, entry members, matches or results. Those fields represent
real domain facts such as withdrawal, injury, suspension or result finality.

## Implementation

- Removed the field from the stage form, filters, list and table validation.
- Removed the column from fresh MariaDB/MySQL and PostgreSQL schemas.
- Added schema update `6.2.0-2026081505.sql` for existing development installs.
- Changed the stage state index to use project, Joomla publication and ordering.
- Confirmed that the external JL3 canonical migration does not emit the removed
  field and added an architecture regression check around the component schema.

## Compatibility

No migrated source value is discarded: JoomLeague 3 did not provide a canonical
stage lifecycle equivalent. Migration-created stages already rely on Joomla
publication and imported schedule data.
