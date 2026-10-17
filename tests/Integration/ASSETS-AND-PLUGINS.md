# Admin Assets And Plugin Acceptance

```sh
TMPDIR=/mnt/disk-a/dev/tmp node tests/Integration/run-admin-assets.mjs
```

The runner installs the component and all five plugin payloads on disposable
Joomla instances for mysqli and pgsql. Seven mandatory Firefox cases cover:
registered JavaScript/CSS availability, real event/statistic dual-list assignment
and removal through Joomla Apply, cross-sport assignment rejection with existing
links preserved, player/staff role filtering, a real content-plugin person link,
and the administrator dashboard destination. The content and quickicon fixtures
explicitly enable their plugins; disabled plugins are not treated as executable.

Nine separately validated native cases exercise public/private/unpublished/
missing persons, legacy name tags, malicious HTML labels, and guest/admin
Quick Icon visibility. Person fixtures for the native shortcode tests live only
in a connection-local temporary table. Every database action requires the
disposable host, database and prefix guards. Mail is disabled.

Real browser tests found incorrectly registered media URIs and dual-list fields
missing from the Joomla form definition. Correct file availability alone does
not prove persistence; the suite checks actual junction-table assignments.
The browser uses a test administrator; project-scoped ACL is covered elsewhere.

All five plugins are installed, but this suite claims behavioral acceptance only
for content and quickicon, not console commands, Finder indexing or scheduled
task execution. It does not contact production services or publish packages.
