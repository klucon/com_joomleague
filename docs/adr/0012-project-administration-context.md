# ADR 0012: Canonical project administration context

- Status: Accepted
- Date: 2026-08-08

## Context

Project settings, rule overrides and template overrides existed as independent administration destinations. Returning from an editor always opened the global project list, and future competition-management areas had no canonical project entry point. Legacy array parameters and browser-history navigation are explicitly out of scope.

## Decision

`project_id` is the only project-context parameter for administration views. A read-only Project Panel loads the canonical project aggregate through `ProjectContextRepository` and becomes the primary destination of the project name in the list.

The panel exposes only implemented operations as links:

- project settings;
- sparse project-rule overrides;
- sparse project-template overrides.

Teams, rounds, matches and standings are shown as planned domains without links until their tables, services and authorization boundaries exist. This avoids presenting global placeholder views as working project features.

Project editing uses Joomla 6.2 `FormController` support for a base64 `return` parameter. The controller already accepts the return target only when `Uri::isInternal()` succeeds. The edit view repeats only that validated value in its POST form. Direct editing from the global list has no return parameter and therefore retains Joomla's normal return to the list.

Rules and Templates use fixed component-internal redirects to the current Project Panel after Save or Close. They do not accept arbitrary return targets and do not use JavaScript history.

## Consequences

- Every project workflow has a deterministic context and return destination.
- Existing Joomla redirect security is reused instead of duplicated.
- Future project domains have one stable URL contract: `project_id=<positive integer>`.
- Shared project identity/profile loading removes duplicate queries from Rules and Templates.
- The panel can evolve as persistence is added without changing existing project URLs.

## Non-goals

- No new competition-runtime table is introduced in this slice.
- Planned project domains remain non-interactive.
- No legacy `pid[]`, `stid[]` or `seasonid[]` compatibility is provided.
