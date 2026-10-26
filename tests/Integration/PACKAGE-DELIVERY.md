# Browser Package Delivery Acceptance

Run from the repository root with actual full-package archives:

```sh
TMPDIR=/mnt/disk-a/dev/tmp \
JL_PACKAGE_ZIP=/absolute/path/to/target-package.zip \
JL_LEGACY_PACKAGE_ZIP=/absolute/path/to/pkg_joomleague-6.2.0-beta6.zip \
node tests/Integration/run-package-delivery.mjs
```

The runner creates disposable Joomla 6.2 RC2 installations on MariaDB and
PostgreSQL. A private HTTP server supplies an update XML and the exact target
ZIP. Only the Joomla application ports are bound to localhost. Mail is disabled.
Production sites, databases and published feeds are not modified.

Nine mandatory Firefox observations per driver cover:

1. Uploading and reinstalling the actual beta6 ZIP using Joomla's installer form.
2. Stable-channel rejection of the offered RC update, with HTTP feed evidence.
3. Saving RC minimum stability using native Joomla Options.
4. Discovering the RC update through Check for Updates.
5. Downloading a bad-checksum package and rejecting it without changes to
   installed extension files, stored fixture data or extension versions.
6. Downloading and installing the same package with its correct SHA-256.
7. Advancing the package and all 22 owned extension versions.
8. Preserving existing sports, Joomla users, permissions and content data.
9. Reinstalling the current target through the upload form, replacing stale
   bundled languages while preserving separate translations and overrides.

The final upload also requires all eight native package acceptance observations
and the database schema contract. Screenshots, JSON reports and HTTP server
request logs are retained under `tests/artifacts/delivery-*/`.

Joomla regenerates component administrator menus and tree coordinates during
reinstallation. Generated JoomLeague administrator menu rows and nested-set
coordinates are excluded from identity comparisons. The baseline uses Joomla's
own menu rebuild so generated paths can remain strictly compared. Other menu
rows, aliases, links and permissions remain checked.
The seven bundled profile/category/variant catalogue tables may legitimately
change with a new package and are outside sports-row equality checks; existing
project and match records remain included. Extra columns introduced by migrations
are allowed, but existing column values and row counts must remain unchanged.

The private feed temporarily replaces enabled update sources. Their original
enabled states are restored before checking official package registration.
No assertion disables or bypasses Joomla's native checksum verification.

## Boundaries

This is an administrator-initiated Joomla update over private HTTP, not an
unattended cron update or a public downloads/authentication service test.
Bad checksums are checked for installed-state preservation, not absence of
temporary extraction: Joomla unpacks the download before verifying its checksum.
Power-loss recovery, malicious archive fuzzing and interrupted DDL recovery are
not certified by these cases.
