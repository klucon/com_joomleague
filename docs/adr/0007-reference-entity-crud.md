# ADR 0007: Standard CRUD for reference entities

- Status: accepted
- Date: 2026-08-08
- Scope: administrator competition and season records

## Context

Projects reference reusable competition and season records. Both entities already had canonical MariaDB and PostgreSQL tables, but their administrator routes were read-only architecture placeholders. Project editing must not be implemented against ad-hoc selectors or direct SQL writes, so these reference entities need a stable Joomla CRUD contract first.

## Decision

Competition and season use the standard Joomla administrator MVC stack:

- plural `AdminController` and `ListModel` classes for list actions;
- singular `FormController` and `AdminModel` classes for editing;
- Joomla `Table` classes for persistence, check-in and state changes;
- XML forms and Search Tools filters;
- component-level `core.create`, `core.edit`, `core.edit.state` and `core.delete` ACL;
- core toolbar, token, validation, pagination and published-state layouts;
- no custom CSS or JavaScript.

UUID values are generated server-side as RFC 4122 version 4 identifiers. They are displayed read-only and are never accepted as an editable identity field. Empty aliases are normalised with Joomla's `OutputFilter::stringURLSafe()`.

Season is presented as a reusable identity such as `2026/27`; administrators are not asked to guess its calendar boundaries. Nullable start and end columns remain available for lossless migration and specialised future workflows. When supplied programmatically, their order is checked before persistence and remains protected by equivalent MariaDB and PostgreSQL constraints. Competition and season deletion checks project references before issuing a delete; the existing `ON DELETE RESTRICT` foreign keys remain the final concurrency-safe guard.

Human-readable names are intentionally not globally unique. Imports and migrations must use UUID, external identifiers and migration provenance rather than assuming that a competition or season name uniquely identifies a record.

## Consequences

- Project forms can depend on stable, paginated competition and season sources.
- Validation failures preserve submitted values in Joomla user state and do not change persisted rows.
- A referenced competition or season produces a domain-specific error rather than exposing a raw foreign-key exception in the normal path.
- Item-level asset ACL is deferred. The current release uses the explicit component ACL already exposed by `access.xml`.
- Country remains a short canonical code in this foundation increment. A richer country selector may replace the text control without changing persistence.

## Verification

- Architecture and language-key verification.
- PHP lint and XML validation.
- UUID format and uniqueness unit test (1,000 generated identifiers).
- Authenticated browser-equivalent CRUD on Joomla 6.2 with MariaDB.
- Authenticated create/search/delete on Joomla 6.2 with PostgreSQL.
- Invalid season chronology rejection and persistence rollback check.
