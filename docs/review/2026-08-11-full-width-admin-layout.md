# Full-width administrator layout

## Changes

- All 16 administrator `layout=edit` templates use the full Joomla `col-12` outer grid width.
- Project schedule and automation fieldsets are stacked at full width.
- Match-result status controls use the complete 12-column row.
- Planned-view placeholders no longer constrain content to 9 of 12 columns.
- Functional grids remain intact, including the Position 5/2/5 dual-list and dashboard columns that already total 12 columns.
- Long machine-readable values use Bootstrap's `text-break` utility so tables do not increase the width of the whole administrator page.
- No custom CSS or JavaScript was introduced.

## Verification

- PHP syntax validation passed for all administrator templates.
- The foundation architecture test enforces full-width editor wrappers.
- `verify-edit-form-width.cjs` measures rendered fieldsets and checks horizontal overflow.
- `verify-admin-layout.cjs` passed all covered pages at 1440x1000 and 390x844.
- Club, team and person create/delete workflows passed after updating their Joomla 6.2 confirmation-button expectations.
- MariaDB and PostgreSQL deployments both report schema `6.2.0-2026081102` as current.

Deployment backup: `/mnt/disk-b/server-backups/joomla62/20260811-182558/`.
