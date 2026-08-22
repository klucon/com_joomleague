# JoomLeague 6.2

Clean development line for JoomLeague 6.2.0.

## Non-negotiable goals

1. Sport-independent behavior driven by versioned sport profiles.
2. Auditable, resumable and lossless migration from JoomLeague 0.93, 1.5, 2.5, 3 and supported V6 alpha releases.
3. Equivalent MariaDB/MySQL and PostgreSQL schemas and behavior.
4. Joomla core MVC, ACL, forms and visual conventions.

Historical source trees are references and migration inputs. They are not copied into this tree wholesale.

## Current foundation

- Forty-eight canonical tables are implemented for MariaDB/MySQL and PostgreSQL, including project aggregates, sport-type runtime catalogs, universal match data and standings.
- Fifteen bundled sport profiles are persisted losslessly and idempotently.
- Six versioned template definitions resolve defaults through profile, project and presentation layers.
- Joomla component configuration is reserved for installation-wide operational settings.
- `en-GB` is the canonical source language; the development package also includes the current `cs-CZ` translation.
- Bundled sport profiles are immutable templates. Positions, event types and statistics become working records only when selected during creation of a local sport type.
- Architecture checks reject database-driver drift, invalid profiles and missing English language constants.

Run the foundation verification with:

```bash
php tests/Architecture/verify-foundation.php
php tests/Unit/verify-template-resolver.php
php tests/Unit/verify-project-rule-validator.php

# Run inside each installed Joomla test container
php /tmp/verify-project-rule-repository.php
```
