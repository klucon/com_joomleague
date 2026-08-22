# Historical project migration matrix

- Date: 2026-08-08
- Sources: JoomLeague 0.93/1.5/2.5/3, current JoomLeague V6 alpha schema and KSM reference schema
- Target: canonical JoomLeague 6.2 project aggregate

## Migration invariant

Every source field receives one of four outcomes:

1. **Canonical**: mapped to a typed canonical column or relation.
2. **Rule override**: parsed and validated against the selected immutable sport profile.
3. **Template override**: converted to a sparse project template setting.
4. **Provenance/issue**: retained in `migration_record.source_payload_json` with a deterministic issue when no accepted target adapter exists.

No field is silently discarded. Missing references create explicit placeholder entities where needed for relational integrity and a migration issue describing the repair.

## Project fields

| Historical field | Target | Outcome and notes |
| --- | --- | --- |
| `id` | `migration_record.source_identity_json` | Source identity; target receives a new ID and UUID. |
| `name` | `project.name` | Canonical, required. |
| `alias` | `project.alias` | Canonical; regenerated only when empty and recorded. |
| `league_id` | `project.competition_id` | Relation through migrated `league` → `competition`. Broken zero/missing IDs create placeholder + issue. |
| `season_id` | `project.season_id` | Relation through migrated season. Broken zero/missing IDs create placeholder + issue. |
| `sports_type_id` | `project.sport_type_id` | Mapped local sport type. |
| KSM `sport_profile_id` | `project.profile_version_id` | Resolve to the exact installed profile version; ambiguous references block migration. |
| `project_type` | `project.project_type` | Legacy enum mapped to profile code (`league`, `tournament`, `friendly`, `race`, etc.); unsupported values create an issue. |
| `timezone` | `project.timezone` | Canonical optional IANA timezone. Invalid identifiers are retained and reported. |
| `start_date` | `project.start_date` | Canonical; zero dates become null plus provenance. |
| `start_time` | `project.default_start_time` | Canonical `HH:MM`; malformed values create an issue. |
| derived end date | `project.end_date` | Optional; calculated only when source semantics are reliable. |
| `current_round_auto` | `project.current_round_mode` | `0=manual`, `1=start`, `2=end`, `3=first_match`, `4=last_match`. |
| `current_round` | deferred round assignment | Stored in provenance until canonical rounds exist, then mapped to `current_round_id`; never treated as a universal string. |
| `auto_time` | `project.auto_advance_seconds` | Canonical non-negative integer. |
| `picture` | `project.picture` | Canonical path after media-path migration. |
| `extended` | `project.description` or `metadata_json` | Parsed extended fields map to accepted typed data; unrecognised values remain in provenance. |
| `published`, `ordering` | same semantic project fields | Canonical Joomla state. |
| `checked_out*`, `modified*`, `modified_by`, `asset_id` | canonical audit/ACL fields | Preserve valid audit values; rebuild assets using Joomla APIs. |
| `is_utc_converted` | migration provenance | Used only to interpret old timestamps; not copied as runtime domain state. |

## Sport-rule fields

| Historical field | Project rule override path | Notes |
| --- | --- | --- |
| `game_regular_time` | `match_structure.default_match_duration_minutes` | Applied only when profile supports timed contests. |
| `game_parts` | `match_structure.period_count` | Validated against profile capabilities. |
| `halftime` | `match_structure.breaks` | Converted to the profile's break representation. |
| `points_after_regular_time` | `standings.points_regular` | Parse legacy `win,draw,loss`; malformed or semantically impossible values create issues. |
| `use_legs` | profile-specific nested score capability | Never interpreted as football-only sets without profile context. |
| `allow_add_time` | `match_structure.extra_time.enabled` | Only for profiles declaring extra time. |
| `add_time` | `match_structure.extra_time` duration | Adapter derives period count/length only when source sport semantics are known. |
| `points_after_add_time` | `standings.points_after_extra_time` | Parsed and validated. |
| `points_after_penalty` | `standings.points_after_shootout` | Parsed and validated. |
| `teams_as_referees` | future project official-assignment policy | Retained in provenance until the official assignment capability is accepted. |

## Presentation fields

| Historical field | Target | Notes |
| --- | --- | --- |
| `fav_team_highlight_type` | `project_template_config(ranking).favorite_highlight_mode` | Normalise historical numeric/string variants to `row`, `name` or `none`. |
| `fav_team_color`, `fav_team_text_color`, `fav_team_text_bold` | future validated ranking fields | Retain in provenance until these fields exist in the template definition. |
| `fav_team` | deferred favourite assignment | Parse source IDs only after canonical project participants exist. |
| `template` | presentation/layout selection | Migrate through an allowlisted template adapter; unknown values remain provenance + issue. |
| `master_template`, `sub_template_id` | flattened sparse overrides | Resolve legacy inheritance first, detect cycles, persist resulting differences, retain source links in provenance. |

## Legacy integrations

| Historical field | Outcome |
| --- | --- |
| `extension` | Retain in provenance until an installed, allowlisted integration adapter claims the value. |
| `enable_sb`, `sb_catid` | Retain as a migration issue for the former prediction/tipping integration; never reinterpret silently. |

## Reusable identity tables

### League → Competition

`name`, `short_name`, `middle_name`, `alias`, `country` and accepted extended metadata map directly. The source league ID is retained as migration identity. Duplicate names are allowed in canonical storage because separate organisations may use identical names.

### Season → Season

`name`, `alias`, publication state, ordering and accepted extended metadata map directly. Optional start/end dates can be derived only from reliable source data; project match ranges do not automatically redefine a reusable season.

## Required fixtures

Before enabling migration writes, fixtures must cover:

- JL 0.93 project with missing modern fields;
- JL 1.5 project migrated through the existing successful community path;
- JL 2.5 and JL3 football league with comma-separated point rules;
- V6 running race project;
- KSM project with profile linkage;
- missing league, season and sport type references;
- invalid timezone and zero dates;
- cyclic `master_template` references;
- duplicate project names across organisations/seasons;
- repeated migration proving idempotent source-to-target identity mapping.
