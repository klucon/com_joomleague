# Project Ownership Acceptance

Run from the repository root:

```sh
php tests/Unit/verify-form-controller-ownership.php
node tests/Integration/run-ownership.mjs
```

The integration runner creates fresh Joomla installations for MariaDB and
PostgreSQL. It never deploys to or writes business data in an existing site.
The candidate is installed from a copy of the working source; publication and
package creation are separate operations.

Local prerequisites:

- Docker access and the cached images `mariadb:11.4`, `postgres:18-alpine` and
  `klucon/joomla-local-runtime:php84-20261004`.
- Cached Joomla archive
  `/mnt/disk-a/docker/local-joomla/packages/6.2.0-rc2.zip`.
- Playwright and Firefox at the paths used by
  `tests/Browser/verify-project-ownership.cjs`.

Coverage includes stages, rounds, matches, project entries, memberships,
standings adjustments and stage transitions. Real project-scoped accounts test
allowed and denied writes, immutable ownership, parent-derived creation,
model-state IDs, publication, deletion, ordering, native CSRF handling and
simultaneous requests from different accounts. Missing fixtures fail the run.

HTTP ports bind only to 127.0.0.1. Mail is disabled before account fixtures are
created. Test databases use temporary memory-backed storage. Containers and their
dedicated network are removed in the cleanup handler. Disposable webroot files
are retained under `/tmp/jl-r1-*` for diagnosis; generated environment and Joomla
configuration files containing credentials are removed. Browser results and
native model logs are written under `tests/artifacts/ownership-*`.
