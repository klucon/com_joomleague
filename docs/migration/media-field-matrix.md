# Legacy media field matrix

Date: 2026-08-08

## Canonical rule

JoomLeague 6.2 stores one source logo per club and one optional source logo per team. It does not store manually maintained small, medium and large logo variants.

| Legacy source | Canonical target | Rule |
| --- | --- | --- |
| JL3 `club.logo_big` | `club.logo` | Direct path migration. |
| JL3 `club.logo_middle` | none | Not imported as the canonical logo. Original source data remains available to the migration audit record. |
| JL3 `club.logo_small` | none | Not imported as the canonical logo. Original source data remains available to the migration audit record. |
| JL3 `team.picture` | `team.picture` | Remains the team photograph; it is never interpreted as a logo. |
| JL3 `playground.picture` | `venue.picture` | Direct path migration to the canonical venue photograph. |

JL3 does not provide a separate team logo field. `team.logo` therefore remains `NULL` after JL3 migration and presentation may inherit `club.logo`. Native JoomLeague 6.2 teams can set an explicit team logo when required.

## Media paths

- Club logos: `images/com_joomleague/clubs`
- Team logos: `images/com_joomleague/teams/logos`
- Team photographs: `images/com_joomleague/teams/photos`
- Venue photographs: `images/com_joomleague/venues`

No `database` directory is introduced into new media paths.
