# Schedule templates (JSON šablony rozlosování)

Team-agnostické JSON šablony, na kterých se bude stavět generování rozlosování
v komponentě (JoomLeague). Šablony **neobsahují ID týmů ani výsledky** — jen
strukturu a pozice. Konkrétní týmy se doplní až runtime resolverem.

## Společný tvar (envelope)

Každý soubor má:

```jsonc
{
  "templateFamily": "schedule",
  "type": "round-robin | single-elimination | double-elimination | swiss | groups-playoff",
  "generation": "static | progressive",
  "version": 1,
  "participantRef": { /* legenda, viz níže */ },
  ...
}
```

- **static** — celé rozlosování / kostra jde předpočítat (round-robin, pavouci, groups-playoff).
- **progressive** — dvojice vznikají až z výsledků, šablona nese jen konfiguraci (swiss).

## Participant reference (klíčová abstrakce)

Účastník zápasu NENÍ ID týmu, ale jedna z variant odkazu:

| Tvar | Význam |
|---|---|
| `{"seed": N}` | pozice z losu / nasazení (1..počet slotů) |
| `{"winnerOf": "<matchId>"}` | vítěz daného zápasu |
| `{"loserOf": "<matchId>"}` | poražený z daného zápasu |
| `{"groupRank": {"group":"A","rank":1}}` | N-tý tým z tabulky skupiny |

Každý zápas má stálé **`id`**, na které se ostatní zápasy odkazují.
Jeden společný **resolver** umí naplnit všechny typy: round-robin používá jen
`seed`, pavouci `seed`/`winnerOf`/`loserOf`, kombinovaný systém i `groupRank`.

## Soubory

| Soubor | Typ | Obsah |
|---|---|---|
| `round-robin-first-half.json`  | static | Berger, každý s každým, **1. polovina**, 2–30 týmů |
| `round-robin-second-half.json` | static | totéž, **2. polovina** (odvety, prohozené doma/venku) |
| `single-elimination.json` | static | pavouk K.O. pro 2,4,8,16,32 (+ zápas o 3. místo) |
| `double-elimination.json` | static | pavouk s opravnými boji (WB+LB+grand finále+reset) pro 4,8,16,32 |
| `swiss.json` | progressive | jen konfigurace + doporučený počet kol dle počtu týmů |
| `groups-playoff.json` | static | kompozice: 4 skupiny po 4 (round-robin) → top 2 → pavouk pro 8 |

## Poznámky ke konzumaci

- **Round-robin**: `schedule[]` = kola; jedno kolo na řádek. Lichý počet týmů →
  v každém kole je `bye` (tým s volnem). Sudý => N−1 kol, lichý => N kol.
- **Single elim**: zápasů = `slots − 1` (+1 o 3. místo). 1. kolo má `seed`,
  další kola `winnerOf`.
- **Double elim**: zápasů = `2·slots − 2` (+1 podmíněný `GF-RESET`).
  `GF-RESET` se hraje jen pokud grand finále vyhraje tým z losers bracketu.
  Vstupy poražených z WB jsou do LB řazeny reverzně (anti-rematch).
- **Swiss**: `perTeamCount[N]` dává `minRounds` (~⌈log₂N⌉) a `recommendedRounds`.
  Párování je nutné řešit runtime (váhové párování / Dutch systém) — nepředpočítává se.
- **Groups-playoff**: `phases[]`. Skupinová fáze odkazuje na round-robin šablonu
  přes `templateRef` (JSON pointer), play-off používá `groupRank` pro nasazení.

## Generátor

Vše je generované a validované skriptem `gen_templates.py`
(počty zápasů, platnost všech odkazů, každý poražený v pavouku spotřebovaný
právě jednou, seedy 1..N právě jednou). Round-robin ověřen proti FIDE Bergerovým
tabulkám pro 6 a 8 týmů.
