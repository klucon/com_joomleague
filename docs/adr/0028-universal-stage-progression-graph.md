# ADR 0028: Universal stage progression graph

## Context

Projects can contain league phases, groups, heats, classifications and knockout stages. Parent-child stage nesting describes presentation structure but cannot express which participants qualify from one stage to another. Encoding football-specific promotion logic in PHP would violate the profile-driven domain boundary.

## Decision

`stage_transition` is a project-owned directed edge between two stages. It stores a stable code, one generic selector contract, optional target seeding and a declared result carry-over mode. Both endpoints are protected by composite stage/project foreign keys. Self-links are rejected by both database drivers and the administration rejects cycles across the complete project graph.

The first selector vocabulary is deliberately sport-neutral:

- all source-stage participants;
- a standings rank range and profile-defined scope;
- winners or losers from all or one source-stage round;
- manual selection.

The canonical database payload remains JSON so selector contracts can evolve without sport-specific columns. Administrators never edit that JSON: Joomla number, list and round controls build and validate the canonical payload in the model. Source-round ownership is revalidated before persistence.

## Safety boundary

This decision introduces topology and validated qualification intent only. It does not automatically rewrite target-stage assignments, carry standings values or remove existing participants. Execution requires an idempotent, audited progression run and remains the unfinished part of administrator roadmap point 1.

MariaDB/MySQL and PostgreSQL expose the same 46-table schema at anchor `6.2.0-2026081503`.

Execution is explicit and preview-first. Every distinct canonical input creates one immutable transition run. Repeating the same input reuses that audit record and synchronizes current assignments. Automatic assignments are tracked separately from manual stage assignments, so recalculation may remove only links owned exclusively by progression. `all_results` and `mutual_results` are executable standings inputs rather than descriptive metadata.
