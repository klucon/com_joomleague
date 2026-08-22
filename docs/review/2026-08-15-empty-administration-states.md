# Empty administration states

The administration was verified after resetting all runtime data.

- Position, event and statistic catalogs use their real SQL aliases while retaining newest-ID-first ordering.
- Typed list view properties no longer mask a database error with a secondary assignment error.
- Rounds, matches and standings opened without their required project context redirect to Projects with translated Joomla warnings.
- The migrations inventory and history view remains available with no migration batches.

Live browser verification covered all seven affected routes on the empty Joomla 6.2 MariaDB test installation.

Deployment backup: `/mnt/disk-b/server-backups/joomla62/20260815-185931`.
