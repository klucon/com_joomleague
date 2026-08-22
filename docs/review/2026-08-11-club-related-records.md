# Club related records

## Scope

The new-club editor offers two independent Joomla Yes/No switchers: create a team and create a venue. Both default to No and are shown only while creating a club.

## Persistence

- The club and requested related records are saved in one database transaction.
- The generated team and venue inherit the club name and short name, receive independent UUIDs and point back to the new club.
- Existing clubs never create related records through these transient switches.
- Technical failures are logged and the complete transaction is rolled back; the administrator receives an en-GB message.

## Verification

- Architecture checks require both Joomla switchers and the transaction boundary.
- A fixture-scoped integration test verifies one linked team and one owned venue on MariaDB and PostgreSQL, then removes only its own records.
