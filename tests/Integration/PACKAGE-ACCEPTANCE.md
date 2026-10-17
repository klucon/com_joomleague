# Full Package Acceptance

Use a locally built and content-audited package, never a production installation:

```sh
TMPDIR=/mnt/disk-a/dev/tmp JL_PACKAGE_ZIP=/absolute/path/pkg_joomleague-6.2.0-rc1.zip node tests/Integration/run-package-acceptance.mjs
```

To test a real beta6 upgrade instead of a current-version reinstall, also set:

```sh
JL_LEGACY_PACKAGE_ZIP=/absolute/path/pkg_joomleague-6.2.0-beta6.zip
```

The runner creates disposable Joomla/database containers on both mysqli and
pgsql. It stages archives into a writable Joomla temporary directory because
Joomla unpacks nested extensions alongside their source archives. Read-only
source mounts remain untouched. Package installation and update run as
www-data after the Joomla webroot is prepared for that user; they do not rely
on running the package installer as Unix root.

Before updating, fixtures contain people, projects, entries, stage transitions
and a final 2:1 result with two periods. Twelve table snapshots must preserve
their existing rows and fields after the update. Additional migration columns
are allowed; lost rows or altered existing fixture fields are not.

Three language targets are deliberately replaced with stale text when present;
missing legacy targets stay missing until the update. Installed files must
then match the current package's bytes. The package must own exactly its 22
children, with matching versions, current media and one enabled package update
server at the configured official URL. Joomla's schema contract also checks
all 59 component tables after updating. Five mandatory native report cases
are independently validated by the parent runner.

This is native Joomla package installation/update, not a browser upload test or
automatic remote update download. It does not validate every possible legacy
database, extension customization or production filesystem layout. Mail is
disabled, generated credentials are removed, and disposable containers are
destroyed. No release is published.
