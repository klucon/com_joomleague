# JoomLeague 6.2 frontend & module roadmap

Ondřej's explicit direction (2026-08-16): reuse as much of the **original JoomLeague 3**
(`com_joomleague_orig`, `components/com_joomleague/{views,modules,plugins}/`) feature set as
possible — it is the canonical, battle-tested design this rewrite is meant to honor. Do **not**
use `com_joomleague_v6` as a source (abandoned Joomla-6 branch that regressed to football-only
assumptions); its module/plugin list matches JL3's only because it copied JL3 — its *code* is
never a reference. Everything is built fresh against the 6.2.0 universal, sport-profile-driven
schema (`project`/`competition`/`season`/`stage`/`round`/`match`/`entry`, `entry_kind`
team/person/group), translating JL3's team-only concepts into entry-based ones.

Working method: for every view, ask "could this reasonably exist as a standalone module too?" —
if yes, pair it. Status updates inline as work lands.

## Architecture rule: shared domain layer, not admin namespace reuse

`mod_joomleague_standings` (the first module built, 2026-08-16) took a shortcut that must not be
repeated: it read standings via `Joomleague\Component\Joomleague\Administrator\Service\StandingsRepository`,
manually registering the `administrator/components/com_joomleague/src` autoload path from
site-side code. This was a real architecture boundary violation (caught in review) — reading a
published standings snapshot is a domain concern usable by admin, frontend views, *and* modules
alike, not an "Administrator" concern. Left as-is, the same shortcut would get copied for every
future module (schedule, results, events, statistics), baking the wrong boundary in permanently.

Correct shape, to retrofit onto `mod_joomleague_standings` and to use from the start for every
module/view pair from here on:

```
shared domain service (read/calculate only, no admin-specific dependency)
    ├── administration (recalculate, corrections — admin-only actions, ACL-gated)
    ├── frontend view (site MVC, once it exists)
    └── companion module (site, read-only)
```

**Done (2026-08-16).** Extracted `StandingsRepository`, `StandingsCalculator`,
`StandingsDecimal`, `CanonicalJson`, `UuidFactory`, `StandingsContractValidator` into namespace
`Joomleague\Component\Joomleague\Domain\Service`. Files stay physically at
`administrator/components/com_joomleague/src/Service/` — that's the only folder Joomla keeps
installed for this component regardless of client, since this is a single-manifest component
(`<namespace path="src">Joomleague\Component\Joomleague</namespace>` + `<files folder="site">`
convention, not a separate package with its own site manifest). Do **not** use `libraries/` at
`JPATH_ROOT` for shared code — that's a reserved Joomla-core directory, not extension territory.

The `\Domain` prefix is registered once in `administrator/components/com_joomleague/services/provider.php`
(`JLoader::registerNamespace('Joomleague\\Component\\Joomleague\\Domain', __DIR__ . '/../src', ...)`
inside `register()`), which fires on every `bootComponent('com_joomleague')` regardless of which
client triggered the boot — unlike the manifest's automatic `\Administrator\*`/`\Site\*`
registrations, which are client-scoped and were exactly why the original shortcut existed.
Site code (views, modules) gets access by calling `Factory::getApplication()->bootComponent('com_joomleague')`
before using anything under `\Domain\Service\*` — no manual `JLoader::registerNamespace()` needed
anywhere else. Use this exact pattern for every future module (`mod_joomleague_matches`,
`mod_joomleague_results`, etc.): identify the read/calculate classes with no admin-specific
dependency, move them into `\Domain\Service\*` (same physical folder, just the namespace
segment), update admin call-sites' `use` statements, and boot the component from site code
instead of reaching into `\Administrator\*`.

One trap hit during this refactor: a moved class can have a *bare, same-namespace* dependency on
a class that wasn't moved (`StandingsCalculator` referenced `StandingsContractValidator` without
a `use` statement, since both started in `Administrator\Service`) — check transitively, not just
the direct class list, or the admin side breaks with a "class not found" until the dependency is
moved too and the classes that still reference it from `Administrator\Service` get an explicit
`use` added.

**Done (2026-08-16/17, second pass).** A follow-up review correctly flagged two remaining issues
even after the namespace move above: (1) `StandingsRepository` still mixed read (`describe()`,
`current()`) and write (`recalculate()`, snapshot/row inserts, `publish()`) responsibility in one
class, so a module only needing read access still pulled in write-capable code; (2) the module
helper loaded metric labels via `Factory::getLanguage()->load('com_joomleague', JPATH_ADMINISTRATOR)`
— reaching into the admin language tree from site code, and the *only* place those labels existed
at all. Fixed: `StandingsRepository` split into `StandingsReader` (read-only; `describe()`,
`current()`, and the now-public `context()` resolver both it and the recalculator need) and
`StandingsRecalculator` (write-only; composes `StandingsReader` instead of duplicating context
resolution — constructor-injects it). All admin call-sites (`StandingsModel`) updated to use
`StandingsRecalculator` for the recalculate action and `StandingsReader` for display. The module
helper now uses only `StandingsReader` and never instantiates the recalculator.

First-ever site-side language files were added at `components/com_joomleague/language/{en-GB,cs-CZ}/com_joomleague.ini`
(previously this component had zero site-side language files), holding just the
`COM_JOOMLEAGUE_STANDING_METRIC_*` keys the domain layer's data needs to render — not a mirror of
the admin `.ini`. **Trap hit and fixed on top of this**: `Factory::getLanguage()->load('com_joomleague', JPATH_SITE)`
alone is *not* enough for a module to find this file. Joomla's real lookup formula for component
language files (`libraries/vendor/joomla/language/src/Language.php::load()`) is
`$basePath/language/$lang/$extension.ini` — i.e. the *root* site language folder, not
`components/com_joomleague/language/`. The reason the admin side has always "just worked" is that
`Joomla\CMS\Dispatcher\ComponentDispatcher` (which only runs when the *component itself* is
dispatched, not for a standalone module) has a two-tier fallback:
`$app->getLanguage()->load($option, JPATH_BASE) || $app->getLanguage()->load($option, JPATH_BASE . '/components/' . $option)`
— that second call is what actually resolves files kept under the component's own directory
(true for both clients: `JPATH_ADMINISTRATOR/components/com_joomleague/language/...` and
`JPATH_SITE/components/com_joomleague/language/...`). A module's helper doesn't go through
`ComponentDispatcher`, so it must replicate that same two-tier lookup itself or translations
silently no-op (Joomla returns the raw language key as text, not an error — this shipped and
rendered live with un-translated `COM_JOOMLEAGUE_STANDING_METRIC_*` strings in the standings
table for a period before being caught and fixed). `StandingsHelper::getStandings()` now does:
`$language->load('com_joomleague', JPATH_SITE) || $language->load('com_joomleague', JPATH_SITE . '/components/com_joomleague')`.
**Apply this same two-tier load pattern in every future module/site-view that needs component
translations** — it is not specific to standings.

Verified live on both deployments (2026-08-17): admin `view=standings&project_id=27` renders
translated headers and correct recalculated rows unchanged; frontend module on
`joomla62.klucon.cz` (project 27, `sidebar-right`) now renders translated column headers
(`Hráno`, `Výhry`, `Remízy`, ...) instead of raw `COM_JOOMLEAGUE_STANDING_METRIC_*` keys, with row
data matching the admin view exactly. No `TypeError`/`Fatal`/`Uncaught` in either container's
logs across the deploy window.

Note this is distinct from the ACL work: reading a *published* standings snapshot on the
frontend has no ACL question (public data). Administrative recalculation/correction of the
table is a separate, already-scoped ACL action (`joomleague.project.edit.results` or a
dedicated action) — the roadmap existing does not imply that ACL point is "done" for standings.

**Auto-recalculation on match result save (2026-08-17).** Until this point, a project's standings
snapshot only got created/refreshed in two ways: an admin opening the admin Standings screen
(`StandingsModel::getCurrent()` always recalculates before displaying — this is why some projects
had a table and others didn't, it depended entirely on whether anyone had ever opened that
screen for that project) or clicking the explicit Recalculate toolbar button. A newly-added
project's frontend module therefore showed "table not published yet" until an admin happened to
visit that admin screen — surprising and not what a 2026-era site should require. Fixed: saving a
match result now cascades into a standings refresh automatically, via a new
`Administrator\Service\StandingsCascadeTrigger`, wired into `MatchresultModel::saveResult()`
(`Administrator\Service\MatchProjectResolver::resolveMatchContext()` resolves the match's
`project_id`/`stage_id` directly from `#__joomleague_project_match`, which already carries both
columns). The trigger republishes **both** the project-wide table (`stage_id = null`) and, when
the match belongs to a stage, that stage's own table, across every scope the project's profile
defines (e.g. `total`/`home`/`away`) — different sport profiles publish standings at either
level, and a save shouldn't need to know which. It composes `StandingsReader` + admin-only
`StandingsRecalculator` exactly like the admin screen does, and is deliberately fire-and-forget:
every failure is caught and logged (`com_joomleague.standings` channel), never allowed to fail
the result save it followed — a standings refresh problem must not block recording a match
result. `StandingsCalculator` already filters matches by `included_result_statuses` from the
profile contract, so triggering on every save (draft or final) is safe — draft results simply
don't contribute rows.

Verified live (2026-08-17): resolved match 3736's context (project 42, stage 42) via the new
resolver, ran the trigger directly, and confirmed six snapshot rows appeared in
`#__joomleague_standing_current` for project 42 (`total`/`home`/`away` × project-wide/stage), where
previously there were none. The frontend module (still pointed at project 42) immediately started
rendering the real table instead of "table not published yet" — no admin screen visit needed.

**Scope note:** this cascade currently fires from match *result* saves
(`MatchresultModel::saveResult()`) and standing *adjustment* create/edit/delete
(`StandingadjustmentModel::save()`/`delete()`, added 2026-08-17 — corrections don't go through a
match result save, so they needed their own hook to stay "no manual recalculate needed"). Lineup/
event/statistics edits that could also affect statistics-based standings metrics are not yet wired
to this trigger — extend `StandingsCascadeTrigger` usage to those save paths if/when a
statistics-driven standings metric needs the same "just works" behaviour.

**Removed: the "generated on" display toggle and its underlying string (2026-08-17).** Both the
module (`show_generated_date` param) and the `ranking` view (same param) showed a small
"Standings generated on <timestamp>" line, meant as a freshness indicator. Removed entirely on
request — now that recalculation is fully automatic (match results, and as of the same day,
standing adjustments too), a visible "when was this generated" timestamp reads as evidence of a
manual step, which is exactly the impression it shouldn't give. Deleted: the param/field from both
XML manifests, the rendering block from both templates, and the now-unused
`MOD_JOOMLEAGUE_STANDINGS_GENERATED`/`COM_JOOMLEAGUE_RANKING_GENERATED` message keys and their
`_SHOW_GENERATED_LABEL`/`_DESC` field-label keys, in every file they'd been duplicated into. The
**admin** Standings screen's own "Generated %s" indicator (`COM_JOOMLEAGUE_STANDINGS_GENERATED`,
unrelated key) was deliberately left alone — that one is a legitimate admin-facing freshness
check while working with the tool, a different concern from a public-facing timestamp.

**Verifying the adjustment cascade taught a real lesson about `StandingsRecalculator`'s
dedup behaviour.** Tested via the actual admin UI (not a script): saved a +1 point adjustment for
FC Rakšice A on project 42 → snapshot correctly went from 18 to 19 points, fresh `generated_at`.
Deleted the adjustment → re-checking the *newest* row in `#__joomleague_standing_snapshot` still
showed 19 and looked like the cascade hadn't fired on delete. It had: `StandingsRecalculator`
dedupes by `input_checksum` and reuses an existing matching snapshot rather than insert a
duplicate when the input reverts to a state it's already seen (here, "no adjustment" matched an
older snapshot from before the test) — so `#__joomleague_standing_current.snapshot_id` had
correctly been re-pointed *backward* to that older row (18 points, confirmed), while the
newest-by-id row in the snapshot table simply wasn't the active one anymore. Check
`standing_current`, not `MAX(id)` on `standing_snapshot`, when verifying a recalculation actually
ran.

**Highlight colour picker (2026-08-17).** Added `highlight_color` (native `type="color"` field,
default `#ffc107`, `showon="highlight_style!:none"` so it's hidden when there's nothing to
colour) to both the module and `ranking` view. Applying it turned out non-trivial for the same
reason the bold-text fix was: **`table-striped` sets `background-color` directly on each
`<td>`/`<th>`, not the `<tr>`** — an inline style on the row itself would just get overridden by
striping on odd rows exactly like `fw-bold` was invisible on `<th>` before. Fixed by computing the
cell style string once per row and applying it to every cell explicitly (rank `<td>`, entry
`<th>`, every metric `<td>`) rather than the `<tr>` — inline style always wins regardless of
class-based competition, so this is unconditionally reliable once applied at the right level.
`highlight_style="row"` sets `background-color` + `color`; `"text"` sets only `color` (whole row's
text, not just the entry name, for consistency with how the bold treatment already worked before
colour existed). Readable text colour is computed automatically from simple perceived luminance
(`0.299r + 0.587g + 0.114b`, threshold 0.6 → black else white) — picking an arbitrary background
colour without this would make some choices unreadable. `Duplicated in both StandingsHelper and
RankingModel (`contrastColor()`), matching the established per-extension copy pattern for this
component. `showon`'s negation syntax is `field!:value` (colon-prefixed bang), not `field:!value`
— confirmed by reading `FormHelper::parseShowOnConditions()` rather than guessing. Verified live:
dark green (`#2e7d32`) → white text, light yellow (`#fff3cd`, the original hardcoded default) →
black text, on both the module and the `ranking` view, `showon` correctly hides the field when
style is "None", color picker renders as Joomla's native minicolors widget with the correct label.

**Overcorrection caught and fixed same day: colour should never have touched "Bold text
only".** The user's original ask was specifically about the *whole row's* colour
("jakou barvou se zvýrazní celý řádek") — I over-generalised and also applied the picked colour
as text colour under `highlight_style="text"`, which (a) was never asked for and (b) broke that
style's actual behaviour: because colour is only meaningful per-cell (see the `table-striped`
note above), applying it meant the rank/metric `<td>` cells only got a `color` style and no
font-weight at all, so "Bold text only" silently stopped being bold on everything except the
entry name — a real regression, not just unwanted scope. Fixed: reintroduced `<tr class="fw-bold">`
for both `row`/`text` styles (bold correctly inherits to every `<td>`; only the entry `<th>` still
needs an explicit class due to the browser's own bold-by-default styling on `<th>`), and scoped
`highlight_color`/`highlight_text_color` to the `row` style exclusively — `text` now renders
exactly as it did before the colour picker existed. `showon` narrowed from `highlight_style!:none`
(shows for row+text) to `highlight_style:row` (row only) to match, in both manifests; field
descriptions updated to drop the now-false "or the entry name's text" line. **Lesson for future
params**: when a param is asked for as "for X", scope its effect to exactly X — don't extend it to
adjacent styles/paths on the assumption that's "more useful," since that assumption itself can
silently break the thing being extended into.

**Row colour and text colour are independent, simultaneous choices, not two alternate modes
(2026-08-17, caught immediately by the user, fixed same turn).** First pass tied
`highlight_color_text` to `showon="highlight_style:text"` — i.e. it only appeared, and only
applied, when the *text-only* style was selected, making it mutually exclusive with
`highlight_color_row`. That was wrong: the actual ask was "what if I want to change the row's
colour *and* the text colour in that row" — both at once, on the "Whole row" style. Fixed:
`highlight_color_text`'s `showon` widened to `highlight_style!:none` (shows/applies for both
`row` and `text`), and the auto-computed contrast colour (`contrastColor()`) was removed entirely
— now that text colour is an explicit, always-available control, an automatic guess would just be
a second, conflicting source of truth. Verified live: `highlight_style="row"` with
`highlight_color_row="#2e7d32"` + `highlight_color_text="#ffeb3b"` produced a green background
with the exact yellow chosen, not an auto-picked black/white.

**Checkboxes silently reverted to the default when all three were unchecked (2026-08-17).**
`highlight_decoration` (`type="checkboxes"`, options bold/italic/underline) reverted to
"bold" every time the admin unchecked all three and saved — reported as "why does Bold turn
itself back on?". Root cause: an HTML checkbox **group** submits nothing at all when every box in
it is unchecked (browsers only submit checked boxes), so the saved menu item params never
recorded an empty selection — the key was simply absent from the stored JSON, indistinguishable
from "this menu item has never touched this field," and the model's read-side fallback
(`$params->get('highlight_decoration', ['bold'])`) then defaulted to bold on every subsequent
load. This is a structural limitation of checkbox groups, not something fixable by changing the
default or the fallback value — no fallback could distinguish "never configured" from "explicitly
set to nothing" once the key is missing either way. Fixed by replacing the single multi-select
checkboxes field with **three independent Yes/No radio switches**
(`highlight_bold`/`highlight_italic`/`highlight_underline`, `type="radio"` +
`layout="joomla.form.field.radio.switcher"`) — a two-option radio group always has exactly one
option selected and therefore always submits a value, regardless of which way the admin sets it,
so there is no way to lose the choice. `metric_codes` (also `type="checkboxes"`) does **not** have
this bug in practice, since its existing semantics already treat "nothing selected" as "no
filter — show every column," which happens to be the same behaviour the missing-key fallback
produces anyway; the underlying HTML submission gap is identical, it just doesn't cause a visibly
wrong default there. **Any future checkbox-group param needs this same check**: if "nothing
selected" needs to mean something *other* than "no filter"/"no restriction" — most obviously
whenever the un-set state has real content (a specific default like this one), or must be
distinguishable from "not yet configured" — the checkboxes field can't safely be used for it at
all; use independent radio switches per option instead. Verified live by writing the saved params
directly to both extremes (all three `"0"`, then all three `"1"`) and confirming the rendered
`font-weight`/`font-style`/`text-decoration` matched in both directions — the real admin-UI submit
was blocked by an apparently pre-existing, unrelated Joomla core menu-item alias-uniqueness quirk
when re-saving the *same* item unchanged (`lib_joomla.ini`'s generic "alias is already being
used" check triggering even against itself); worth another look if it recurs, but confirmed
unconnected to this fix — the same read-path code is exercised regardless of how the params JSON
was written.

**Three more table improvements, each independently toggleable, all off by default (2026-08-17).**

- **Zone lines** (promotion/relegation-style markers): `zone_top_enabled`/`zone_top_count`/`zone_top_color` and the equivalent `zone_bottom_*` triple. Draws a coloured border on the boundary row only (`border-bottom` on the last row of the top zone, `border-top` on the first row of the bottom zone) — not a filled background across every zone row, matching "hraniční čára" (dividing line) rather than a solid block. Ranks are resolved against the *full* row count before the row-limit window (if any) slices `$rows` down, since a zone boundary is an absolute position, not relative to whatever subset happens to be displayed. Verified live: 14-entry table, top zone size 3 → border on rank 3 (Hrušovany nad Jev.), bottom zone size 3 → border on rank 12 (SK Újezd u Brna, `14 − 3 + 1`) — both math and rendering confirmed.
- **Recent form** (`form_enabled`/`form_count`): a "Form" column showing each entry's last N results as small coloured W/D/L badges, most recent on the right. Required a genuinely new read method — `StandingsReader::recentForm()` — not just a display tweak: this sport profile's contract uses `outcome_source: "root_numeric"` (confirmed live by reading a real project's payload), meaning win/draw/loss is *not* stored anywhere; it's derived by comparing the two participants' root scores using `StandingsDecimal::compare()`, the exact same decimal comparison `StandingsCalculator::outcomes()` already uses for the table itself — reused rather than reimplemented with floats, so a form badge can never disagree with the table it sits in. Only supports two-participant head-to-head matches (the only mode this component's outcome derivation currently implements at all); non-2-participant matches are silently skipped. Verified live via a standalone script against all 14 real entries in project 42, cross-checked against their actual win/draw/loss totals for plausibility.
- **Responsive columns** (`responsive_columns`): below the Bootstrap `md` breakpoint, hides every metric column except the last one (rank/entry/form always stay visible) instead of letting the table scroll sideways. "Last metric" as the one guaranteed-visible column is a reasonable default since sport profiles conventionally list the deciding metric (points, etc.) last — no per-metric "priority" concept exists to pick a better one from.

**Two more toggles, requested after seeing the table live (2026-08-17).**

- **Combined score format** (`combined_score_format`): merges any `*_for`/`*_against` metric pair
  still present after column filtering into a single `xx:xx` column ("30:9"), matching JL3's old
  display convention instead of two separate columns. Built generically — `StandingsModel::buildColumns()`
  scans the filtered metric list for any code ending `_for` with a present `_against` counterpart
  sharing the same prefix (works for `score`, `legs`, `sets`, `maps`, `rounds`, `games`,
  `score_points`, not hardcoded to football's `score_for`/`score_against`) and collapses the pair
  at whichever position it's first encountered, regardless of which half came first in the
  contract's own metric order. `'metrics'` in the returned array became `'columns'`, a list of
  `{type: single, code}` or `{type: combined, for, against, prefix}` — the template branches on
  `type` per column instead of iterating raw metrics directly.
- **Short column labels** (`short_labels`): swaps full-word headers for short Czech-table-style
  ones (Z/V/R/P/B/+/-...) where one is actually defined (`COM_JOOMLEAGUE_STANDING_METRIC_*_SHORT`
  keys) — deliberately **not** attempted for every metric code that exists (`WINS_OVERTIME`,
  `STATUS_ORDER`, `RESULT_RANK`, etc. have no established short form and would just be guessing);
  those fall back to the full label. The fallback mechanism (`Text::_()` returns the key unchanged
  when undefined, compared against the input to detect "not found") is reused for both `_SHORT`
  and the earlier `_COMBINED` suffix, so an unlisted metric or prefix degrades to its normal label
  instead of ever printing a raw untranslated key.

Verified live: with both toggles on, header row showed `Z / V / R / P / Skóre / +/- / B` and a
value cell showed `30:9`; with `combined_score_format` off and `short_labels` on alone, the same
row showed separate `Skóre+`/`Skóre-` columns instead of one — the two toggles compose correctly
and independently. No errors in either container's logs.

**Short-label follow-up: fixed "Score" abbreviation, Form column short label, hover tooltips
(2026-08-17).** `SCORE_COMBINED_SHORT` had been left as the full word "Skóre"/"Score" rather than
an actual abbreviation — fixed to `S` (and `SCORE_FOR_SHORT`/`SCORE_AGAINST_SHORT` tightened from
"Skóre+"/"Skóre-" to "S+"/"S-" for the same single-letter convention). Added a short form for the
Form column too (`COM_JOOMLEAGUE_STANDINGS_COLUMN_FORM_SHORT` = "F"), which previously never
participated in the short/full resolution at all — it used a single always-on key. Added a new
`short_label_tooltips` toggle (default **on**, unlike every other toggle this session, which
default off — a hover tooltip has no visual/layout impact of its own, only value, so there is no
"unexpected regression" risk to guard against by defaulting it off) — when a header's short form
differs from its full label, the `<th>` gets a plain HTML `title="<full label>"` attribute; no JS,
works everywhere. Verified live: header row rendered `Z(title="Hráno") / V(title="Výhry") / ... /
S(title="Skóre") / +/-(title="Rozdíl ve skóre") / B(title="Body") / F(title="Forma")`; turning the
tooltip toggle off removed every `title=` attribute from the page (`grep -c "title="` → 0).

**Short/full label mismatch on `played` (2026-08-17).** Czech `PLAYED_SHORT` was `"Z"` (for
"Zápasy" — football-flavoured "matches"), but the base label it's a short form *of* is `"Hráno"`
("Played" — deliberately the sport-neutral term already used everywhere else in this component, a
generic verb that fits matches, races, legs, games alike). Short and full form referring to
different underlying words, not just different lengths of the same word, was a real inconsistency
— fixed by changing the short form to `"H"` (matching "Hráno" directly) rather than picking a
football-specific word to abbreviate. English was already consistent (`"P"` for `"Played"`,
same word) and needed no change. General principle for any future short label: the short form
must abbreviate the *actual* full label this component already uses for that metric, not a
different (even if closely related) word picked independently — otherwise the tooltip pairing
becomes misleading rather than clarifying.

**Ported to the module, feature-complete parity (2026-08-17).** Everything built and verified on
the `standings` site view this session — highlight row/text colour split, bold/italic/underline
radio switches, zone lines, recent form, combined score format, short labels + tooltips,
responsive columns — is now also in `mod_joomleague_standings`. Rewrote `StandingsHelper.php` and
`tmpl/default.php` to mirror `Site\Model\StandingsModel`/`tmpl/standings/default.php` structurally
(same param names, same `buildColumns()`/`markZoneBoundaries()` logic, same
`columnLabel`/`columnTooltipAttr` template helpers) rather than reinventing it, keeping only the
module-specific bits (`show_project_name`, `moduleclass_sfx`, reading params from the module's own
`Registry` instead of `Factory::getApplication()->getParams()`). The metric `_SHORT`/`_COMBINED`
language keys (including the `PLAYED_SHORT="H"` fix) needed **no duplication** — they already live
in the shared site `.ini` both the module and the site view load via the same two-tier fallback;
only the module's *own* field-label keys (params form labels, `MOD_JOOMLEAGUE_STANDINGS_*`) needed
adding, mirroring the site view's admin-`.ini` additions. Verified live with every toggle on
simultaneously: short+tooltipped headers (`H`/`V`/`R`/`P`/`S`/`+/-`/`B`/`F`), responsive classes on
all but the last two columns, combined score (`30:9`), zone lines on ranks 3 and 12 of 14, and the
highlighted row (FC Rakšice A, rank 7) showing the exact configured background/text colours — same
as the site view, on the module. No errors in either container's logs; module edit screen clean
(no raw keys, no PHP warnings).

**Table roadmap considered closed for now.** Both delivery surfaces (module, site view) for
`ranking`/`standings` have full feature parity. Remaining known gap — linking an entry's name to
its profile — stays blocked on `teaminfo`/`clubinfo` not existing yet (see below); everything else
requested this round is done.

**Not built: linking an entry's name to its profile.** Blocked on a real dependency, not skipped for convenience — `teaminfo`/`clubinfo` (the views such a link would point to) are still `planned`, not built, per the Views table above. Linking to a page that doesn't exist would just be a broken link; revisit once one of those views ships.

**Project-scoped module config fields (2026-08-17).** `mod_joomleague_standings` gained three
params driven by real usage feedback: `highlight_entry_id` (highlight a team/entry's row —
combined with `limit`, the shown row window centres on it instead of always starting at rank 1)
and `metric_codes` (checkbox picker of which columns to show, doubling as the "what's even
available" answer since it's labelled with the same translated text the table itself uses).
Both need their options scoped to whichever project the module is already configured for — e.g.
`highlight_entry_id` must only list that project's own entries, not every entry on the site.
Joomla's built-in `sql`-type field can't do this (its query is static, no access to a sibling
field's value at render time). Fixed with a real custom Joomla form field per param
(`fields/entry.php` → `JFormFieldEntry extends ListField`, `fields/metrics.php` →
`JFormFieldMetrics extends CheckboxesField`), both reading `$this->form->getValue('project_id', $this->group)`
inside an overridden `getOptions()`.

**Registration trap (apply to every future module needing a custom field):** these classes
cannot use the module's own PSR-4 namespace (`Joomleague\Module\Standings\Site\Field\...`,
`addfieldprefix="..."`) — that only autoloads when the module is actually *dispatched* (site
render), and the admin module editor builds this form without booting the module at all, so
`class_exists()` fails silently there and Joomla falls through to its next resolution strategy.
Use the classic convention instead: `addfieldpath="modules/mod_x/fields"` on `<fields name="params">`
+ a plain (non-namespaced) `class JFormField<Type>` per file — this path-based `require_once` works
identically in both the site and admin execution contexts, since `JPATH_ROOT` is the same in both.

**Second trap, `CheckboxesField` specifically:** its layout (`layouts/joomla/form/field/checkboxes.php`)
reads `$option->checked` directly (for the "no stored value yet, use this option's default state"
case) — `HTMLHelper::_('select.option', ...)` does not set that property, so appending its return
value straight into `getOptions()` without explicitly setting `$option->checked = false` produces
a live "Undefined property: stdClass::$checked" warning per checkbox, right there in the rendered
admin page. `ListField`'s `<select>` layout has no equivalent requirement — this is specific to
checkbox/radio-style fields.

Verified live (2026-08-17): highlighted FC Rakšice A (rank 7/14) with `limit=5` — rendered rows
5–9, FC Rakšice A centred with a `table-warning` row class; `metric_codes=[played,points]`
rendered a 2-column table instead of all 8. Both reverted to defaults after testing.

**Module edit screen shows raw sys.ini keys, not a bug in our language files (2026-08-17).**
Reported as "MOD_JOOMLEAGUE_STANDINGS_XML_DESCRIPTION shows up literally, untranslated, even after
clearing cache" — confirmed via screenshot of the module's own edit screen
(`com_modules&task=module.edit&id=...`), specifically the name/description info box above the
Project field. Root cause identified by reading Joomla core directly: `com_modules`'s
`ModuleModel::preprocessForm()` calls `$lang->load($module, $client->path) || $lang->load($module, $client->path . '/modules/' . $module)`
when building the edit form — this loads **only** `mod_x.ini`, never `mod_x.sys.ini` — yet the
edit template (`administrator/components/com_modules/tmpl/module/edit.php`) displays
`Text::_($this->item->xml->description)`, which is a **sys.ini-only** key. Every other admin
screen that shows a module's name/description (Module Manager list, "New Module" type picker,
Extensions Manager: Manage) evidently loads `.sys.ini` through a different, bulk-listing code
path, which is why those all resolved correctly while this one specific screen didn't — this was
genuinely inconsistent between admin screens, not a caching or deployment problem (page caching
was already off; the deployed files were byte-identical on both webroots the whole time). Fixed
by duplicating just the two sys.ini-only keys (`MOD_JOOMLEAGUE_STANDINGS`,
`MOD_JOOMLEAGUE_STANDINGS_XML_DESCRIPTION`) into the regular `.ini` file too, in both languages —
harmless duplication, `.sys.ini` stays authoritative for the screens that load it. **Apply the
same duplication to every future module's regular `.ini`** — this is a Joomla core inconsistency,
not something specific to this module, so it will resurface for `mod_joomleague_matches` etc.
unless done from the start.

**Frontend title fallback.** A module instance's Title defaults to the raw technical name when
created without the admin renaming it (both `mod_joomleague_standings` module rows in this
deployment ended up this way) — and unlike the name/description above, Title is genuinely free
text Joomla never translates, so it would render literally as the on-page heading if left as-is.
`Dispatcher::getLayoutData()` now checks `$this->module->title === $this->module->module` and, if
so, substitutes `Text::_('MOD_JOOMLEAGUE_STANDINGS')` before the parent dispatch flow's later
`clone` (in `ModuleHelper::renderModule()`) picks up the title for the chrome heading — verified
live, frontend heading changed from literal `mod_joomleague_standings` to "JoomLeague - Tabulka
soutěže". Reuse this same guard in every future module's `Dispatcher::getLayoutData()`.

**Highlight style option (2026-08-17).** Added `highlight_style` (`row` / `text` / `none`,
default `row`) so the highlighted entry's visual treatment is a real choice, not hardcoded —
`row` keeps the original whole-row `table-warning fw-bold`, `text` drops the background
(`fw-bold` only, relying on CSS inheritance from `<tr>` to its cells), `none` renders no class at
all even though the entry is still tracked. The row-limit centring behaviour is independent of
this — it stays active on `none` too, since centring and the visual cue are separate decisions.
Verified live: all three values produced the expected `<tr>` class on FC Rakšice A's row.

**`<th>` bold-by-default trap, found via real usage (2026-08-17).** With `highlight_style="text"`,
the entry-name cell showed no visible highlight at all — reported as "# and points are
highlighted but the team isn't." Cause: `<th>` is bold by the browser's own default user-agent
stylesheet regardless of any class *we* add, so inheriting `fw-bold` from the `<tr>` (which works
fine for the `<td>` rank/metric cells, which are normal-weight by default) has zero visible effect
on a `<th>` that's already bold in every row. Fixed by giving the entry-name `<th>` an *explicit*
class every row — `fw-normal` when not highlighted (or highlighted with style `none`), `fw-bold`
only when highlighted with style `row`/`text` — rather than relying on inheritance from the `<tr>`
for that one cell. An element's own class always wins over an inherited value regardless of
selector specificity, so this is the correct fix, not a workaround. Confirmed both Bootstrap
utility classes carry `!important` (`.fw-normal{font-weight:400!important}`,
`.fw-bold{font-weight:700!important}` in the deployed `template.min.css`), so there's no
remaining specificity ambiguity. **Any future module reusing `<th scope="row">` for a highlighted
identity column needs this same explicit-both-states treatment**, not just an inherited class from
the row.

## Status legend

`done` · `in progress` · `planned` · `investigate` (JL3 behaviour not fully understood yet) ·
`deferred` (decided against, with reason)

## Views — priority order

View and module status are tracked **separately** — a module being done does not mean its
paired frontend view exists (they're independent deliverables that happen to share data).

"Menu item?" reflects JL3's actual `metadata.xml` `hidden` flag per view — i.e. whether that
view was ever meant to be directly assignable as a Joomla site Menu Item (a real navigable page
an admin can add to the site menu), versus a purely internal/support view with no menu entry.

| # | View (JL3 name) | What it shows | Menu item? | View status | Paired module | Module status |
|---|---|---|---|---|---|---|
| 1 | `ranking` | Standings table for a project/division/round | yes | **done** (2026-08-17) — first real site view, verified live as a menu item | `mod_joomleague_standings` | **done** (2026-08-17, live on joomla62.klucon.cz, project 27, verified admin+frontend match) — reads via read-only `\Domain\Service\StandingsReader`; recalculation is admin-only via `StandingsRecalculator`, see architecture note above |
| 2 | `teamplan` / `clubplan` / `nextmatch` | One entry's fixtures / a club's combined fixtures / next-match teaser — unified into one read pattern | yes (all three) | `teamplan`: **done** (2026-08-18); `clubplan`/`nextmatch`: planned, share the same `MatchesReader` | generalized `mod_joomleague_matches` (project/entry/club-scoped param) | planned — next up |
| 3 | `results` | Completed matches for a project/round | yes | **done** (2026-08-17) — second real site view, self-contained (no shared Domain\Service reader) | `mod_joomleague_results` | planned |
| 4 | `ical` | iCal feed (`format=ics`) of a team/club/project's fixtures | **no** (JL3: hidden) — feed endpoint, not a page | planned, high value, cheap once #2 exists | — (feed endpoint, not a widget) | n/a |
| 5 | `eventsranking` | Leaderboard by event type (scorers, cards...) | yes | planned | `mod_joomleague_eventsranking` | planned |
| 6 | `statsranking` | Statistics leaderboard | yes | planned | `mod_joomleague_statranking` / `mod_joomleague_sports_type_statistics` | planned — decide if 6.2's sport-profile system unifies these two into one |
| 7 | `projects` *(new, not in JL3)* | Browse/entry point listing public projects | yes | planned — needed before anything above is reachable via navigation | — (browse page) | n/a |
| 8 | `teams` | List of entries (JL3: teams) for a project/division | yes | planned | — | n/a |
| 9 | `clubs` | List of clubs | yes | planned | — | n/a |
| 10 | `clubinfo` | Club profile (description, venue, colours) | yes | planned | `mod_joomleague_logo` (badge display) | planned |
| 11 | `teaminfo` | Entry profile (description, venue, colours, recent form) | yes | planned | — | n/a |
| 12 | `roster` | Entry's member/player list | yes | planned | `mod_joomleague_teamplayers` | planned |
| 13 | `matchreport` | Single match detail: result, lineups, events, stats | yes | planned | — (deferred companion: live ticker) | n/a |
| 14 | `matrix` | Results grid, all-play-all, links to club/roster | yes | planned, medium | — (grid doesn't fit a widget) | n/a |
| 15 | `resultsmatrix` | Simplified/printable results grid | yes | **investigate** — likely a display variant of `matrix`, may not need a separate view | — | n/a |
| 16 | `resultsranking` | Ranking derived from results | yes | **investigate** — JL3 has no separate model for it, likely a `ranking` display variant | — | n/a |
| 17 | `treetonode` | Elimination bracket / tree tournament | yes | **done** (2026-08-17/18) — click a team's name to trace its whole path through the bracket (matches + connector lines highlight, second click clears) | — (needs real estate, not a widget) | n/a |
| 18 | `teamstats` | Per-entry statistics | yes | planned, lower priority | `mod_joomleague_teamstats_ranking` | planned, lower priority |
| 19 | `player` / `person` | Person profile (generalize beyond "player") | `player`: yes · `person`: **no** (JL3: hidden — internal detail handler behind `player`) | planned, lower priority | `mod_joomleague_randomplayer`, `mod_joomleague_birthday` | planned, lower priority |
| 20 | `staff` | Team staff (coaches etc.) | yes | planned, lower priority | `mod_joomleague_teamstaffs` | planned, lower priority |
| 21 | `rivals` | Head-to-head history between two entries | yes | planned, lower priority | — | n/a |
| 22 | `stats` | Season-wide statistics overview | yes | planned, lower priority | — (overlaps #5/#6, revisit after those exist) | n/a |
| 23 | `curve` | Fever chart — rank progression across rounds | yes | planned, low priority | — | n/a |
| 24 | `referee` / `referees` | Match officials | yes (both) | planned, lower priority | — | n/a |
| 25 | `playground` | Venue profile | yes | planned, lower priority | `mod_joomleague_playgroundplan` | planned, lower priority |
| 26 | `about` | About-this-installation info page (JL3: "About JoomLeague" credits/version page) | yes | planned, low priority — content adapted to describe this deployment/component, not a literal copy of JL3's credits text | — | n/a |

### JL3 views intentionally not carried forward
`backbutton`/`footer`/`projectheading` — JL3-internal hidden layout helpers (`hidden="true"`,
never menu-item-eligible). Their function (breadcrumb/back-navigation, shared footer) belongs
in the site component's layout scaffolding when it's built, not tracked as standalone views.

## Plugins — priority order

| # | Plugin (JL3 name) | What it does | Status |
|---|---|---|---|
| 1 | `quickicon/joomleague_quickicon` | Admin dashboard quick icon linking to the component | **done** — 6.2.0 already has this (verified by `tests/Architecture/verify-foundation.php`) |
| 2 | `content/joomleague_person` | Shortcode `{jl_player}First Last{/jl_player}` linking a name in any article to that person's profile | planned — pairs naturally once view #19 (`person`) exists |
| 3 | `search` (classic Joomla search) or Smart Search (`com_finder`) integration | Indexes clubs/persons/venues/teams for site search | planned, medium — decide classic `plg_search` vs. modern `plg_finder` at build time (JL3 used classic; `com_joomleague_v6` used Finder — Finder is the current Joomla-recommended approach) |
| 4 | `content/joomleague_comments` | Adds comments to match/person pages | **investigate** — hard-depends on the third-party JComments extension in JL3; needs a native-Joomla-comments or no-op alternative before it's buildable |
| 5 | `extension/joomleague_esport` | "Clan" wording variant for esports — JL3 description literally says "currently only wording changes" | **deferred, obsolete** — fully superseded by 6.2.0's universal sport-profile terminology system; this plugin's entire purpose no longer applies |
| 6 | `system/single_login` | Auto-login across sites sharing a session | **investigate** — looks specific to Ondřej's own multi-site (klucon.cz) infrastructure rather than a general JoomLeague feature; confirm relevance before building |

## First site view: `standings` (2026-08-17, renamed from `ranking` same day)

`components/com_joomleague/src/` had been an empty stub all project long (`Extension/JoomleagueComponent.php`
only) — this is the first real site page, built so standings can finally be assigned as a Joomla
menu item, not just shown via the module. Files: `src/Controller/DisplayController.php`
(`default_view = 'standings'`), `src/Model/StandingsModel.php` (reads `project_id`/`stage_id`
straight off `Input` — they're menu-item **request** fields, part of the link's query string, not
params — and reuses `Domain\Service\StandingsReader`, same as the module: read-only, no
recalculation capability from the site), `src/View/Standings/HtmlView.php`,
`tmpl/standings/default.php`.

**Renamed from `ranking` to `standings` (same day, on request):** the view was originally built
using JL3's historical name for this concept (`ranking`, matching the "View (JL3 name)" column in
the table below) — but every *other* piece of this component already settled on "standings"
(`mod_joomleague_standings`, the admin `StandingsController`/`StandingsModel`/`view=standings`, the
`COM_JOOMLEAGUE_STANDING_METRIC_*` keys). Having the menu item type read "Standings" while its
link pointed at `view=ranking` was a real, user-facing inconsistency, not just an internal one.
Renamed everything: folders (`src/View/Ranking` → `src/View/Standings`,
`tmpl/ranking` → `tmpl/standings`), classes (`RankingModel` → `StandingsModel`), the controller's
`default_view`, the CSS class prefix (`com-joomleague-ranking__*` → `com-joomleague-standings__*`),
and every `COM_JOOMLEAGUE_RANKING_*` language key across all four files that had accumulated them
(site `.ini`, admin `.ini`, admin `.sys.ini`, both languages). Two keys needed a different suffix
rather than a straight `RANKING_`→`STANDINGS_` swap — `COM_JOOMLEAGUE_STANDINGS_TITLE` and
`COM_JOOMLEAGUE_STANDINGS_EMPTY` already existed for the *admin* Standings screen, so the site
view's equivalents became `COM_JOOMLEAGUE_STANDINGS_VIEW_TITLE`/`_VIEW_EMPTY` instead — checked
for collisions before renaming, not after. The saved test menu item (id 5106)'s `link` was updated
in the database from `view=ranking` to `view=standings` directly, since Joomla has no
rename-a-menu-item-type operation.

**No changes needed to `services/provider.php`.** Traced Joomla's own dispatch chain to confirm
this rather than assume it: `ComponentDispatcherFactoryInterface::createDispatcher()` builds the
class name as `\{namespace}\{ucfirst($app->getName())}\Dispatcher\Dispatcher` and falls back to
Joomla's generic `ComponentDispatcher` when that class doesn't exist (true here — no custom Site
Dispatcher was added) — this resolution is driven by the *active application's* client, entirely
independent of which concrete `JoomleagueComponent` class got registered as `ComponentInterface`
in the provider (still `Administrator\Extension\JoomleagueComponent`, registered once for both
clients, same as before). `Site\Extension\JoomleagueComponent` (the empty stub) stays unused —
harmless, since `MVCFactory`/`ComponentDispatcherFactory` never consult it.

**Menu item type registration — reverse-engineered from Joomla core, not guessed.** For a view to
show up in "Menu Item Type" and be configurable, `components/com_joomleague/tmpl/<view>/default.xml`
must exist with root `<metadata>` containing a `<layout title="..."><message>...</message></layout>`
child (read by `com_menus`' `MenutypesModel::getTypeOptionsFromLayouts()`, which scans
`components/com_x/tmpl/*` folders — **not** `src/View/*` — for view names) and, separately,
`<fields name="request">`/`<fields name="params">` (read by `ItemModel::getForm()` when
editing/creating the actual menu item instance — same file, two different consumers, two
different xpaths into it). Confirmed live: `com_joomleague` didn't show up in the type picker at
all before this file existed. `request` fields (`project_id`, `stage_id` here) become the menu
item's `link` query string when saved — **submit them as `jform[request][name]`, not baked into
`jform[link]` directly**; the save flow needs the individual fields to validate and rebuild the
link server-side (learned by trial: baking `project_id` straight into `jform[link]` still failed
required-field validation on the separate `request.project_id` field).

**Same "wrong language file" trap, once more, in a third location.** The type picker showed raw
`COM_JOOMLEAGUE_RANKING_VIEW_DEFAULT_TITLE`/`_DESC` keys even though they were correctly defined
in the site `com_joomleague.ini` — because `MenutypesModel::getTypeOptions()` loads
`$component.sys` from `JPATH_ADMINISTRATOR` (i.e. the **admin** `com_joomleague.sys.ini`), never
the site `.ini`. Fixed by duplicating just that title/desc pair into
`administrator/components/com_joomleague/language/{en-GB,cs-CZ}/com_joomleague.sys.ini` too — third
occurrence of this exact pattern this session (module description, module edit screen, now the
menu type picker); by now assume every *new* Joomla admin screen that shows one of this
component's strings loads its own particular combination of files, verify each one live rather
than assuming the fix from the last screen carries over.

**Fourth occurrence of the same trap: the menu item's own edit screen (2026-08-17).** After fixing
the type picker, the *menu item edit* screen (`com_menus`, editing the saved item) still showed
raw `COM_JOOMLEAGUE_RANKING_REQUEST_*`/`_PARAMS_*` keys for the request/params field labels this
view's own `tmpl/ranking/default.xml` defines. Traced to `ItemModel::getItem()`'s `type ==
'component'` switch case: `$lang->load($args['option'], JPATH_ADMINISTRATOR) ||
$lang->load($args['option'], JPATH_ADMINISTRATOR . '/components/' . $args['option'])` — loads the
**admin's own regular** `.ini`, not the site one where these keys were defined. Confirmed this
really is how Joomla resolves it (not something broken only for us) by comparing against the
pre-existing "Home" menu item (`com_content`, `view=featured`): its own non-global field labels
(`COM_CONTENT_FIELD_SHOW_TAGS_LABEL` → "Tags", `COM_CONTENT_FIELD_INFOBLOCK_POSITION_LABEL` →
"Position of Article Info") resolved correctly there too, proving core components rely on this
exact same admin-`.ini` load and duplicate their menu-field strings into it deliberately. Fixed by
duplicating the six request/params keys into
`administrator/components/com_joomleague/language/{en-GB,cs-CZ}/com_joomleague.ini`. **Running
tally of "which file does this specific screen load" for `com_joomleague`, so the next view isn't
guesswork:** module description → module's own `.ini` needs the `.sys.ini` pair duplicated in;
module edit screen title/desc → same duplication, different screen; frontend module title
fallback → site `.ini` (already loaded normally); menu type picker → **admin** `.sys.ini`; menu
item edit screen → **admin** regular `.ini`. Four different files, four different screens, all
for describing the *same* extension.

Verified live (2026-08-17): menu item type "Standings" appeared correctly under the "JoomLeague"
accordion group with translated title/description; created a real test menu item (id 5106,
"Tabulka soutěže - test", Main Menu, project 42) through the actual admin form (not a raw DB
insert, to exercise the real nested-set save logic); the resulting page rendered correctly both
via the raw query string and the auto-generated SEF URL
(`/index.php/tabulka-souteze-test?view=ranking&project_id=42&stage_id=0`), full table, translated
headers, generated-date line, page `<title>` set to the project name. No errors in either
container's logs. **V1 scope deliberately excluded** the highlight/metric-filter fields the module gained — kept
minimal to ship and verify the core mechanism first. **Added same day, on request**: the view now
has the same `highlight_entry_id` (project-scoped picker, centres the row-limit window),
`highlight_style` (`row`/`text`/`none`), `limit`, and `metric_codes` (project-scoped checkbox
picker) params as the module, under the SAME `params` fieldset in `tmpl/ranking/default.xml`. The
two project-scoped custom fields (`entry`/`metrics`) are **separate copies** at
`components/com_joomleague/fields/{entry,metrics}.php` — not shared with the module's
`modules/mod_joomleague_standings/fields/` versions — because `com_menus`' menu item editor loads
this component's own `addfieldpath`, not the module's; the only code difference is which field
*group* holds `project_id` (`"request"`, hardcoded, vs. the module's `$this->group` since there
`project_id` and the picker field share the same `"params"` group). `RankingModel`/`default.php`
got the identical windowing/highlight-class logic as `StandingsHelper`/the module's template —
copy, not shared, same reasoning as the fields (different extensions, no natural shared home
without inventing one prematurely).

**Fifth occurrence of the file-loading trap, same session.** The new params' field labels showed
raw on the menu item edit screen until duplicated into the **admin** `.ini` (same file
`ItemModel::getItem()` already required for the `request` field labels — this one was at least
already known from the fourth occurrence, so no new investigation needed, just apply the same
fix). Verified live: labels resolved, `highlight_entry_id` dropdown correctly scoped to project
42's 14 entries only, `metric_codes` checkboxes rendered without the `stdClass::$checked` warning,
and a live params test (project 42, FC Rakšice A, `limit=5`) produced the same centred/highlighted
row on the site view as it does in the module. No errors in either container's logs.

## Second site view: `bracket` / `treetonode` (2026-08-17/18)

Elimination-bracket rendering for knockout/cup sport profiles: `src/Model/BracketModel.php`,
`src/View/Bracket/HtmlView.php`, `tmpl/bracket/default.php`. Renders every round as a column of
match cards connected by SVG lines from feeder matches to the match they feed into, with
prev/next round navigation that lands on the stage the visitor actually asked for (earlier,
larger qualifying rounds push later rounds' vertical position away from `y=0`, so naively landing
at the top-left corner could look empty until the visitor manually scrolled to find their stage).

**Click-to-trace team path.** Clicking a team's name anywhere in the bracket highlights every
match card that team appears in, plus every connector line joining two of those highlighted
matches — tracing the team's whole run through the tournament at a glance. Second click on the
same team (or elsewhere) clears it. Deliberately scoped to the team *name* span, not the whole
match card — the card itself is reserved for a future click-through to a match detail page.

## Third site view: `teamplan` (2026-08-18)

One project entry's own fixture list — past results and future (unplayed) matches, from that
entry's point of view (opponent, home/away, own score first). `src/Model/TeamplanModel.php`,
`src/View/Teamplan/HtmlView.php`, `tmpl/teamplan/default.php` + `default.xml` (`project_id` +
`entry_id` request fields, `entry_id` using the same project-scoped `JFormFieldEntry` custom field
standings' `highlight_entry_id` already uses).

**Shared read pattern, built for reuse from the start.** Unlike `results` (deliberately
self-contained), this introduces `Domain\Service\MatchesReader` — a new shared reader alongside
`StandingsReader`, following the same rule (read-only, safe from admin/site/module). Its core query
is keyed by entry id and returns matches *from that entry's perspective* (opponent name, own score
first, home/away flag); this is written to accept a list of entry ids internally even though
`forEntry()` only calls it with one, specifically so the still-planned `clubplan` view (a club's
several teams combined) and `nextmatch` teaser can reuse it directly without duplicating the joins.

**Real bug found and fixed while building this: blank opponent/team names.**
`#__joomleague_project_entry.display_name` is usually empty in real data — it only *overrides* the
linked team/person's own name, it is not a copy of it. `MatchesReader`'s first draft selected
`entry.display_name` directly for the *opponent* side (the *own*-entry lookup already had the
correct `COALESCE(display_name, team.name, person name, 'ID n')` fallback, copied from
`components/com_joomleague/fields/entry.php`) — caught immediately by testing against real data
(project 42), where every opponent name rendered blank. Fixed by adding the same team/person
left-joins and `COALESCE` to the participants query. **This same bug already existed, live, in the
already-shipped `results` view** — `ResultsModel::getResults()`'s participants query had the exact
same `entry.display_name`-only select with no fallback, confirmed live on
`joomla62.klucon.cz?option=com_joomleague&view=results&project_id=42` (every match showed a blank
`–` between two empty team names before the fix). Fixed there too, verified live afterward: real
team names on every row. **Any future match/participant query needs this fallback** — never select
`entry.display_name` alone.

**Also fixed while here: `results` and `bracket` were both missing their admin `.sys.ini`
`*_VIEW_DEFAULT_TITLE`/`_DESC` duplication** — the exact trap documented under the `standings` view
below ("menu type picker... loads the **admin** `.sys.ini`"), which the project's own in-file
comment in `com_joomleague.sys.ini` explicitly warns future views to avoid. Both views had working
site pages but would have shown raw untranslated keys in the admin "Menu Item Type" picker. Added
`teamplan`'s pair alongside the two missing ones; verified all three resolve correctly by loading
`com_joomleague.sys.ini` the same way `MenutypesModel::getTypeOptions()` does and reading each key
back with `Text::_()`.

Verified live: `?option=com_joomleague&view=teamplan&project_id=42&entry_id=377` renders "FC
Rakšice A", 13 matches, correct home/away score ordering (e.g. an away match shows opponent's score
first) cross-checked against `MatchesReader`'s own raw output; the "no entry configured" and
"entry not found" error paths both render translated Czech text, not raw keys; no
`Fatal`/`Uncaught`/`Warning` in the container logs across the whole test session.

**Fully verified through the real admin menu-item form (browser automation, not just direct URL
access or a DB check).** Logged into `joomla62.klucon.cz/administrator` and drove the actual "New
Menu Item" flow: opened the Menu Item Type picker (confirmed live — the `.sys.ini` fix above makes
Bracket/Fixtures/Results and schedule/Standings all show translated titles+descriptions, not raw
keys), picked "Fixtures", set the project. Item saved (id 5109), reopened, `entry_id` options
correctly scoped to project 42's 15 entries, picked "FC Rakšice A", saved again. Confirmed in the
database (`link = index.php?option=com_joomleague&view=teamplan&project_id=42&entry_id=377`) and on
the real front-end SEF URL (`/plan-zapasu-test-fc-raksice-a` → HTTP 200, correct heading, clean
logs).

**Params expanded on request (2026-08-18), after the first ship felt too bare next to `results`/
`standings`.** Added `scope` (all/upcoming/played), `order_desc` (newest first), `limit` (cap
shown rows, same idiom as standings' `limit` — plain `array_slice` after filtering/ordering),
`highlight_next` (visually sets the next upcoming match apart), `show_round` (toggle, joins
`show_venue` as the second on/off display flag). Filtering/ordering/limiting all happen in
`TeamplanModel::getPlan()`, not the reader — `MatchesReader` still returns the full, unfiltered,
chronologically-ascending list, so the future `clubplan`/`nextmatch` views can apply their own
independent windowing over the same raw data.

**Real bug caught live while adding `highlight_next`: "next match" must be date-aware, not just
score-aware.** First pass defined "the next match" as simply the first row where `!played` in
ascending order — but `played` only reflects whether a *result was ever recorded*, not whether the
match's date has actually passed. Historical imports contain old matches nobody ever entered a
score for; tested live against project 27 (which does have real 2026 fixtures ahead), the highlight
first landed on a **2021-03-06** match with no score — nearly 5 years in the past — because it was
simply the earliest score-less row. Fixed: a match only counts as "upcoming" (for both the
`scope=upcoming` filter and `highlight_next`) when it is unplayed **and** `scheduled_start` is on or
after `Factory::getDate()->toSql()`; `scope=played` stays purely score-based, since that fact is
unambiguous regardless of date. A stale unplayed/undated old match now correctly appears only under
`scope=all`, not miscategorized into either filtered view. Verified live afterward against project
27 (has 10 genuinely future-dated matches): the highlighted row was 2026-08-23, matching a
standalone re-computation of the same logic against the raw `MatchesReader` output byte-for-byte.
(A first "verification" of this fix was itself a false alarm — misread which HTML element a
`grep -B` context window belonged to, blamed the code before re-reading the markup correctly; the
lesson from the MOL-Cup-import session about not trusting a hasty read of your own dump output
applies here too.)

**Visual redesign (2026-08-18), after real data made the plain list feel too bare — then corrected
same day to drop custom CSS entirely.** Ondřej added real future fixtures via the admin UI and
asked for a much nicer listing. First pass added a large custom inline `<style>` block (grid
layout, hand-picked hex colours, a bespoke "date chip" component) — justified at the time by
`tmpl/bracket/default.php` already having its own `<style>` block, but bracket's case is different
(SVG connector lines between rounds genuinely can't be built from Bootstrap alone). Rejected
outright: "vůbec se mi to designově nelíbí", followed by "doufám, že používáš zásadně pouze
integrovaný BS5 a CSS a nestavíš to na vlastních stylech!!!" — see
[[com-joomleague-site-view-css-convention]]. **Rebuilt with zero custom CSS**, matching `results`'
own established idiom exactly: `list-group`/`list-group-item` rows, `d-flex justify-content-between
align-items-center flex-wrap gap-2`, `badge bg-secondary fs-6` for the score, `badge bg-light
text-dark border fs-6` for an unplayed match's kickoff time, `badge bg-secondary-subtle
text-secondary-emphasis border` for the home/away (D/V) marker, `list-group-item-warning` (a native
Bootstrap contextual class, not a custom colour) plus a `badge bg-warning text-dark` label for the
highlighted next match, `text-muted small mt-1` for the round/date/venue meta line — the same
`h5 mt-4` section-heading convention `results` already uses per round, reused here per
Nadcházející/Odehrané section. `IntlDateFormatter` is still used for locale-aware date text (a
data-formatting concern, not styling), now rendered as one `"23. srpna 2026, 15:00"`-style string
via badges/text instead of a bespoke multi-part chip. The mobile wrapping bug from the CSS-grid
attempt disappeared on its own once back on Bootstrap's own `flex-wrap`/`gap-2` — re-verified with
fresh desktop + 390px mobile screenshots (`docker exec browser_lab node ...`), both consistent,
clean container logs.

**Pushed further same day: "still too plain" feedback, answered with richer Bootstrap-only
components instead of custom CSS.** Dropping the `<style>` block (previous entry) fixed the "not
integrated" complaint but the result — plain `list-group` rows — still read as bare. Rebuilt again,
still zero custom CSS, this time reaching for more of what Bootstrap 5.3 (confirmed bundled:
`/media/vendor/bootstrap/css/bootstrap.min.css`, v5.3.8) and the template's own bundled FontAwesome
6.7.2 (`/media/vendor/fontawesome-free`, lazy-loaded by Cassiopeia on every page already — not an
addition) actually offer: a `card text-bg-primary bg-gradient` hero for the next match (pulled out
of the grid so it isn't shown twice), a `row row-cols-1 row-cols-md-2 g-3` card grid for the rest
instead of a flat list, `fa-regular fa-calendar-days`/`fa-solid fa-location-dot` icons, and a
`border-start border-4 border-{success,danger,secondary}` left edge on played-match cards colouring
win/draw/loss — computed from `own_score`/`opponent_score`, a genuinely new small piece of display
logic, not just markup. Every class used (`text-bg-primary`, `bg-gradient`, `border-secondary-subtle`,
`text-bg-light`, `rounded-pill`) confirmed present in the actual bundled Bootstrap build before
relying on it, not assumed from memory of a typical Bootstrap 5 setup. One legibility fix after the
first screenshot: the home/away pill used `text-bg-secondary-subtle`, whose light background made
the single D/V letter hard to read at card scale — switched to solid `text-bg-secondary`. Verified
with fresh desktop + mobile screenshots and a clean container log.

**Real bug this surfaced: `entry_id` marked `required="true"` blocks creating a brand-new item at
all.** This field's option list depends on `project_id`, which is `0` at the moment the request
fieldset first appears (right after picking the menu item type, before the item has ever been
saved) — so on a fresh item there are no real entries to choose from yet, and a `required` field
stuck at `0` fails HTML5/Joomla validation, meaning **the very first save could never succeed**.
Same underlying timing gap as `highlight_entry_id` (a `params` field, tested only via *editing* an
already-existing item in the earlier verification — never through this codepath before). Fixed by
removing `required="true"` from `entry_id` — enforcement stays server-side in
`TeamplanModel::getPlan()` (`COM_JOOMLEAGUE_TEAMPLAN_NO_ENTRY`). The real, intended workflow for any
future request field with this same dependency: save once with just the parent field set, then
re-open the item — by then the dependent field's options resolve correctly, since `project_id` is
now persisted rather than the just-created default.

## Build order (current recommendation)

1. ~~`mod_joomleague_standings`~~ — **done**
2. ~~`results` site view~~ — **done** (2026-08-17); ~~`teamplan` site view~~ — **done** (2026-08-18, shares `MatchesReader`)
3. `clubplan` / `nextmatch` (reuse `MatchesReader`) + generalized `mod_joomleague_matches`
4. `mod_joomleague_results`
5. `ical` feed
6. `mod_joomleague_eventsranking` / `mod_joomleague_statranking`
7. Remaining first-wave site view: `projects` (browse/entry point)
8. `matchreport` (match detail)
9. `teams`/`teaminfo`/`clubs`/`clubinfo`/`roster` (entry & club profile pages) + `plg_content_joomleague_person`
10. ~~`treetonode` (bracket view)~~ — **done** (2026-08-17/18); `matrix`/`resultsmatrix`/`resultsranking` (investigate + build)
10. Search integration (`plg_finder_joomleague` recommended over classic `plg_search`)
11. Remaining lower-priority views/modules: `teamstats`, `player`/`person`, `staff`, `referee(s)`,
    `playground`, `curve`, `rivals`, `stats`, `about`
