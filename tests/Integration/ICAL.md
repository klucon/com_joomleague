# Calendar Confidentiality Acceptance

Run `node tests/Integration/run-ical.mjs` from the repository root.

The runner installs the current component into disposable Joomla 6.2 RC2
instances on MariaDB and PostgreSQL. It reuses the guarded ownership fixture;
production and existing local installations are not changed. Generated passwords
are not retained in artifacts and mail is disabled.

Coverage:

- Native calendar model: guest and two users with disjoint Joomla view levels,
  project, entry, event and club scopes.
- Actual frontend logins through Firefox and the native Joomla login form.
- HTTP downloads and denied exports, including an unavailable club scope.
- Exact private/no-store policy on success and 404 responses, MIME type,
  attachment payload marker, byte length and nosniff.
- Joomla system page-cache plugin enabled in the disposable instances.
- URI-only test reverse proxy: authenticated user first, guest, other user,
  original user; no access-dependent response may be retained. A public response
  positive control proves that the proxy actually stores and serves cache hits.
- Public project export followed by unpublication at the same URL; conditional
  request headers cannot restore a stale calendar or produce a 304 response.
- Unit regressions separately exercise model errors and unexpected exceptions
  before response payload headers are set.

Evidence is written to `tests/artifacts/ical-<run-id>/`. This is a controlled
cache-policy acceptance test, not verification of every external CDN/proxy
configuration. Existing caches populated by an older version must be purged
when deploying the fix.
