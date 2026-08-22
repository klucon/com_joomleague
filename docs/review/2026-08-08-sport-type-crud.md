# Sport type CRUD implementation review

## Delivered

- Converted the read-only Sport Types page to Joomla `ListModel` administration.
- Added create/edit, publish, unpublish, check-in and delete controllers.
- Added profile, publication and search filters with standard pagination.
- Added a described form for local name, alias, stable code and immutable profile version.
- Added project usage count to the list.
- Prevented deletion and profile rebinding while projects use the sport type.
- Split project and component timezone inheritance into contextual language keys:
  - project: `Use Default (JoomLeague Settings)`;
  - component: `Use Default (Joomla System Settings)`.

## Verification

- Foundation: 15 profiles, 12 equivalent database tables, 983 en-GB keys, 18 menu views, 6 template definitions and 124 project-rule fields.
- All PHP files pass syntax checking and all administration forms parse as XML.
- Sport Type list and edit form return HTTP 200 on MariaDB and PostgreSQL.
- A temporary basketball-derived sport type was created and deleted through Joomla controllers on both drivers.
- Both timezone selectors render the correct first inheritance option.

Review URL: <https://joomla62.klucon.cz/administrator/index.php?option=com_joomleague&view=sporttypes>

## External review

## Suggested next slice

Expose project rule overrides using the existing profile-provided rule schema, validator and repository. The editor must render only paths explicitly declared overridable by the selected immutable profile version and must retain inheritance when a value is unset.
