# Joomla 6.2 reference entity CRUD review

## Delivered

The former Competition and Season proposal screens are now functional Joomla administration views.

### Lists

- Search Tools search and published-state filter.
- Joomla ordering selector and pagination.
- Add, edit, publish, unpublish, check-in and delete toolbar actions according to component ACL.
- Checked-out state, project usage count and stable ID.
- Search by `id:<number>` as well as entity-specific text fields.

### Forms

- Competition: official, middle and short names; alias; internal and external codes; organisation; country code; description; state; ordering; UUID and ID.
- Season: name; alias; description; state; ordering; UUID and ID. Unknown calendar boundaries are intentionally not requested in the normal administrator workflow.
- Every configurable field has an en-GB description.
- Descriptions use the Joomla editor field; UUID and ID are read-only.

### Persistence and safety

- Server-generated RFC 4122 version 4 UUID.
- Joomla-generated alias when left empty.
- Country code normalised to uppercase.
- Season end date cannot precede its start date.
- Project references block competition and season deletion before the database's `ON DELETE RESTRICT` guard.
- Standard Joomla controllers provide CSRF checks, ACL checks, check-in and return handling.
- Queries use bound parameters and separate placeholders for every `LIKE`, avoiding driver-specific repeated-parameter behaviour.

## Verification results

- Foundation: 15 profiles, 12 equivalent tables, 884 en-GB keys, 18 menu views, 6 template definitions and 124 project-rule fields.
- PHP lint: all administrator PHP files pass.
- XML: all component XML files parse.
- UUID test: 1,000 unique valid version 4 identifiers.
- MariaDB web flow: list/form load, create, search, update with stable ID, alias generation, country normalisation, invalid-date rejection, unchanged persistence after rejection and delete all pass.
- PostgreSQL web flow: authenticated form load, create, search and delete all pass.
- Test records were removed after verification.

## Test deployment

- Public review: <https://joomla62.klucon.cz/administrator/index.php?option=com_joomleague&view=competitions>
- Seasons: <https://joomla62.klucon.cz/administrator/index.php?option=com_joomleague&view=seasons>
- Installed in both `joomla62-dev-app` (MariaDB) and `joomla62-dev-postgresql-app`.
- Package: `dist/com_joomleague-6.2.0-dev.zip`
- SHA-256: `959e1ab60e28f61a40901909640037399d1d8f949235975b4aa31887b2069b87`

## Open review items