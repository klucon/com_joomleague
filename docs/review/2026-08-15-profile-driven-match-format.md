# Profile-driven match format

`contest.type` describes the shape of one contest, not the kind of participant.
`head_to_head` therefore means exactly two project entries against each other;
those entries may represent teams, people, pairs or another profile-supported
kind. `race` allows multiple entries in the same contest.

The match editor now renders the immutable profile value as a translated Joomla
list instead of exposing a free-text implementation code. The save path always
reloads the value from the project's immutable sport-profile version, preventing
a modified request from assigning a format that conflicts with the project.

The match list resolves participant names in one batched query and displays
them in slot order. This supports team, person and group entries without
hard-coding football-specific home/away storage into the presentation layer.

The round match list was then reduced to match number, contest, date/time,
result, translated status, a compact Joomla button group, publication and ID.
The immutable contest format no longer repeats on every row.
## Match scheduling editor

The round match list is the primary scheduling workspace. For a two-slot contest it exposes participant slot 1 (home), participant slot 2 (away), match number, local date, local time and attendance directly in each row.

Changes are saved independently per row through `matches.saveInline`. The endpoint requires a Joomla POST token and `core.edit`, validates both entries against the stage entry selection and converts project-local date/time to UTC. Existing participants cannot be replaced after a result, lineup, event or statistic has been recorded, because doing so would change the meaning of historical competition data.

The same participant and scheduling fields are available in the full match edit form. Participant labels are presentation labels for the current two-slot profile; storage remains generic through numbered match-participant slots and project entries.
