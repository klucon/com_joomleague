# Nález: editor rozpisu zápasů podporuje jen 2 účastníky (head-to-head), ne `race` profily

**Datum:** 2026-08-16
**Zjištěno při:** Bod 1 admin roadmapy (univerzální terminologie a chování řízené profilem)
**Závažnost:** Funkční mezera, ne kosmetika — pro `race` profily obrazovka neplní svůj účel.

## Shrnutí

Komponenta definuje v `resources/sport-profiles/*.json` dva typy soutěže (`contest.type`):

- `head_to_head` — 13 profilů (fotbal, hokej, basketbal, šachy, tenis, volejbal, floorball, futsal, bowling,
  darts, esports, MMA/box, rugby).
- `race` — 2 profily (`motorsport.json`, `running-race.json`).

Hlavní administrační obrazovka pro rozpis a přiřazení účastníků k zápasu
(`administrator/components/com_joomleague/tmpl/matches/default.php`) a její ukládací logika
(`administrator/components/com_joomleague/src/Service/MatchScheduleEditor.php`) ale **nikde nekontrolují
`contest_type`** a natvrdo počítají přesně se 2 sloty:

```php
// src/Service/MatchScheduleEditor.php
$home = (int) ($data['participant_slot_1'] ?? 0);
$away = (int) ($data['participant_slot_2'] ?? 0);
...
$desired = [1 => $home, 2 => $away];
```

Šablona zobrazuje pevně dva sloupce „Domácí" / „Hosté", každý s jedním `<select>` na účastníka
(`tmpl/matches/default.php`, sloupce `participant_slot_1` / `participant_slot_2`).

## Dopad

Pro projekt založený na profilu `motorsport` nebo `running-race` **nejde přes tuto obrazovku vytvořit
závod s více než dvěma účastníky** — ne že by se to jen špatně jmenovalo, funkčně to nejde použít k
účelu, ke kterému je profil určený (závod s libovolným počtem startujících).

Naproti tomu navazující logika je na vícero účastníků **už připravená**:

- `#__joomleague_match_participant.slot_number` nemá v DB schématu žádné omezení na hodnoty 1–2, jen
  unikátnost `(match_id, slot_number)` — schéma unese libovolný počet slotů.
- `src/Service/StandingsCalculator.php` už `mode`/`contest_type` rozlišuje a pro `head_to_head` explicitně
  vyžaduje přesně 2 účastníky (`Head-to-head standings require two participants per match.`), což naznačuje,
  že autor počítal i s jiným (více-účastnickým) módem.

Bug je tedy izolovaný na vstupní bod (přiřazení účastníků k zápasu), ne na celou architekturu soutěží.

## Rozsah („blast radius")

Koncept „Home/Away" (2 sloty) se v kódu vyskytuje přesně ve 3 souborech, nikde jinde:

- `src/Model/MatchModel.php`
- `src/Service/MatchScheduleEditor.php`
- `tmpl/matches/default.php`

Ostatní navazující obrazovky zápasu (sestavy/Matchlineup, rozhodčí/Matchofficials, události/Matchevents,
statistiky/Matchstatistics, výsledek/Matchresult) na `participant_slot_1/2` nezávisí a pracují obecně přes
`project_entry_id` / `match_participant`.

## Co by oprava vyžadovala

Nejde o přejmenování textů. Oprava vyžaduje:

1. V `tmpl/matches/default.php` větvit UI podle `contest_type` z aktivního profilu — pro `head_to_head`
   zůstat u 2 sloupců (přejmenovaných na neutrální „Účastník 1" / „Účastník 2", pokud i tady chceme
   univerzální pojmy), pro `race` nabídnout variabilní seznam N účastníků (přidat/odebrat řádek).
2. V `MatchScheduleEditor::save()` nahradit pevné `participant_slot_1/2` obecným zpracováním pole
   účastníků proměnné délky, včetně validace (min. počet startujících podle profilu, žádné duplicity).
3. Ověřit dopad na `MatchesModel::getItems()` a `MatchParticipantSummaryProvider`, které dnes implicitně
   předpokládají max. 2 sloty při skládání zobrazovaného souhrnu zápasu (`participant_entries[1]`,
   `participant_entries[2]` v `tmpl/matches/default.php`).
4. Otestovat proti oběma `race` profilům (motorsport, running-race) end-to-end: založení projektu, generování
   rozpisu, přiřazení účastníků, zápis výsledku, tabulka.

**Odhad rozsahu:** samostatný úkol, ne součást terminologického průchodu. Doporučuji řešit jako vlastní bod
roadmapy, ne bokem v rámci bodu 1.
