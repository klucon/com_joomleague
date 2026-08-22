# Runtime event and statistic catalogs

Date: 2026-08-08

## Implemented

- Replaced the planned Event Types and Statistics placeholders with runtime-table administration lists.
- Both views use Joomla ListModel, Search Tools, tables, cards and pagination without custom CSS.
- Event Types support search, Sport Type, timeline and publication filters.
- Statistics support search, Sport Type, statistic type, scope and publication filters.
- Lists read only materialized runtime records and never decode bundled profile JSON.
- Empty states explain which Sport Type initialization switch creates each catalog.
- Responsive columns keep the working identity visible without horizontal document overflow.
- Dashboard domain status now reports both catalogs as available.

## Boundary

This increment is read-only. Create, edit, state and delete workflows will be introduced together for positions, event types and statistics so they share one ACL and validation contract.

## Review limitation

## Verification

- PHP, XML and foundation architecture checks pass.
- The package installs successfully on both Joomla 6.2 development database drivers.
- The complete administrator layout, including Event Types and Statistics, passes desktop and mobile Playwright checks on MariaDB and PostgreSQL.
- No production deployment was performed.
