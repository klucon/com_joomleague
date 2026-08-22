# Persons mobile overflow correction

Date: 2026-08-08

The Persons list exposed both primary display name and the separate first-name column on narrow screens. Together with selection and publication controls this exceeded the administrator viewport.

The secondary first-name column now follows the same Joomla/Bootstrap responsive contract as other secondary name columns and is hidden below the `md` breakpoint. The primary name remains visible for every record, including mononymous persons, because the list already falls back from last name to first name.

No custom CSS or JavaScript was introduced.

The complete desktop/mobile administrator layout suite passes on the MariaDB and PostgreSQL Joomla 6.2 development instances after the correction.
