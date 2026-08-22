# ADR 0018: Sport Type position capabilities

Date: 2026-08-08

Position-to-event and position-to-statistic capabilities belong to the local Sport Type layer. Project-level activation is a separate future override layer and must not redefine basic compatibility.

The canonical junction tables are `position_event_type` and `position_statistic`. Both persist `sport_type_id` and use composite foreign keys to each endpoint. Unique `(id, sport_type_id)` owner keys on positions, event types and statistics make cross-sport assignments impossible even through direct SQL. Assignment identity is the endpoint pair; ordering is explicit and deletion of an endpoint cascades only its capability links.

The Position editor exposes separate Event Types and Statistics tabs after the position has first been saved. Both use a Bootstrap/Joomla dual-list without custom CSS, load only definitions owned by the same Sport Type, preserve submitted ordering, validate IDs against authoritative queries, use standard component ACL and CSRF handling, and replace both assignment sets in the position-save transaction. They never write profile JSON. Project activation will inherit this capability set and may only narrow it.