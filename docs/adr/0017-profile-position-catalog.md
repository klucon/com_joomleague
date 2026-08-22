# ADR 0017: Sport-type runtime catalogs

Date: 2026-08-08

## Status

Accepted.

## Context

Bundled sport-profile versions are immutable templates. Administrators need editable working positions, event types and statistics for each local sport type, while projects must retain exact profile-version provenance. Showing profile JSON directly in the working catalogs incorrectly suggests that records exist before an administrator creates them.

## Decision

- Runtime definitions are stored in `sport_position`, `event_type` and `statistic` and belong to one `sport_type`.
- A new Sport Type form offers independent `Create positions`, `Create event types` and `Create statistics` switches, all enabled by default.
- The switches are transient initialization commands. They are hidden and ignored when an existing sport type is edited.
- Selected definitions are validated before writes and materialized with the exact `source_profile_version_id`, `source=profile` and canonical definition checksum.
- Sport Type creation and all selected definitions are committed in one transaction. Any failure rolls back the complete operation.
- Unique `(sport_type_id, code)` constraints prevent duplicate local identities. Deleting an unused sport type cascades to its runtime catalogs.
- Profile payloads remain immutable templates and are not edited when runtime records change.

## Consequences

Fresh installations have empty working catalogs even though bundled profiles exist. Catalog rows appear only after a sport type is created with the corresponding initialization option. Administrators can also create a sport type without any sample definitions and build its catalogs later through future CRUD workflows.

## Review limitation