# Restricted SQL Import Acceptance

```sh
php tests/Unit/verify-sql-insert-data-parser.php
node tests/Integration/run-sql-import.mjs
```

The runner reuses the isolated Joomla infrastructure described in `OWNERSHIP.md`.
It additionally reads `dist/com_joomleague-6.2.0-beta6.zip` to verify a real beta6
upgrade with preserved business data. No published archive is overwritten.

For MariaDB and PostgreSQL it checks:

- Native installation, upgrade, repeated installation and schema contracts.
- Rejection of executable SQL, unrelated tables/columns, custom DDL and cycles.
- Full schema/data export and restore into a different database/prefix, even
  with reversed statement order and reverse-ID self-referencing hierarchies.
- Literal/Unicode preservation, idempotent retry and generated identities.
- Atomic failed batches and trusted-DDL recovery, including existing data.
- Canonical profile materialization, retained local roles and missing profiles.
- Refusal to import through a view into unrelated underlying tables.
- Real HTTP upload, dedicated import permission, guest/CSRF denial, safe errors
  and concurrent requests from separate sessions.

The import permission is component-wide and intentionally separate from project
editing permissions. These exports do not include Joomla core accounts/ACL or
files. Only packaged driver-specific schema statements and data-only INSERTs
are supported; this is not an arbitrary SQL dump executor.

The tests create only disposable databases, use synthetic data, disable outgoing
mail and bind HTTP to loopback. Containers and their network are removed on
completion/failure. Results are saved under `tests/artifacts/sql-import-*`.
