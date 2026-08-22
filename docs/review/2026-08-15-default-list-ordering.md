# Default administration list ordering

All JoomLeague administration list models now default to `a.id DESC`. Their Joomla search-tools forms use the same default so a new administrator session consistently displays the newest records first.

The architecture verifier scans every `ListModel` and every `filter_*.xml` form. It fails when either layer introduces a different default order.

Live browser verification covered 16 global and project-context lists on the Joomla 6.2 MariaDB test installation. The selected ordering and rendered row IDs were both checked.

Deployment backup: `/mnt/disk-b/server-backups/joomla62/20260815-184013`.
