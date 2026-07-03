# Frontend přepis

Tento dokument mapuje přepis frontendové části staré komponenty do aktuální struktury `com_joomleague_v6`.

## Zdroj a cíl

Zdroj pro porovnání:

```text
/mnt/disk-a/dev/com_joomleague/components/com_joomleague
```

Cílový zdroj:

```text
/mnt/disk-a/dev/com_joomleague_v6/components/com_joomleague
```

Starý strom slouží jako zdroj chování, datových dotazů a šablonových očekávání. Kód se nepřenáší 1:1. Nové provedení musí odpovídat Joomla 6 struktuře, namespacingu, databázovým API, filtrování vstupů a escapování výstupů.

## Aktuální stav v6

Frontend v6 už obsahuje tyto hlavní pohledy:

| v6 pohled | Účel |
| --- | --- |
| `projects` | seznam projektů |
| `project` | detail projektu |
| `teams` | seznam týmů |
| `team` | detail týmu |
| `clubs` | seznam klubů |
| `club` | detail klubu |
| `person` | detail osoby |
| `playground` | detail hřiště |
| `schedule` | rozpis zápasů |
| `results` | výsledky |
| `ranking` | tabulka pořadí |
| `stats` | statistiky |
| `roster` | soupiska |
| `referees` | rozhodčí |
| `matchreport` | detail zápasu |

Z toho plyne, že část starých samostatných pohledů se má slučovat do existujících v6 pohledů místo zakládání další vrstvy historických názvů.

## Mapování starých pohledů

| Starý pohled | Stav ve v6 | Cílové řešení |
| --- | --- | --- |
| `about` | nepřenášet jako komponentní view | informace patří do webu/dokumentace |
| `backbutton` | nepřenášet | řešit layoutem nebo navigací šablony |
| `footer` | nepřenášet | řešit šablonou webu |
| `projectheading` | částečně pokryto | sdílený layout nebo metoda ve `SiteHtmlView` |
| `clubinfo` | částečně pokryto | rozšířit `club` |
| `teaminfo` | částečně pokryto | rozšířit `team` |
| `clubplan` | částečně pokryto | základní klubový rozpis je v `schedule&club_id=` |
| `teamplan` | částečně pokryto | základní týmový rozpis je v `schedule&projectteam_id=` |
| `nextmatch` | částečně pokryto | preview a historie jsou doplněné do `matchreport` |
| `matrix` | částečně pokryto | základ převzat do `resultsmatrix` |
| `resultsmatrix` | pokryto základním pohledem | nový v6 pohled `resultsmatrix` |
| `resultsranking` | pokryto základním pohledem | nový v6 pohled `resultsranking` slučuje výsledky a tabulku |
| `statsranking` | pokryto základním pohledem | nový v6 pohled `statsranking` pro pořadí podle hráčských statistik |
| `teamstats` | pokryto základním pohledem | nový v6 pohled `teamstats` pro souhrn a statistiky týmu |
| `eventsranking` | pokryto základním pohledem | nový v6 pohled `eventsranking` pro pořadí podle zápasových událostí |
| `rivals` | pokryto základním pohledem | nový v6 pohled `rivals` pro soupeře a vzájemnou bilanci týmu |
| `treetonode` | chybí | nový pohled pro turnajové stromy |
| `curve` | chybí | odložit, historicky grafový výstup |
| `ical` | pokryto základním pohledem | nový v6 pohled `ical` a kalendářový blok v `schedule` |
| tipovací soutěž | chybí | stará JoomLeague ji měla v core; pro v6 navrhnout po stabilizaci hlavních soutěžních výstupů |
| `player` | slučovat | použít `person` s kontextem hráče |
| `staff` | slučovat | použít `person` s kontextem realizačního týmu |
| `referee` | slučovat | použít `person` nebo `referees` podle kontextu |

## Doporučené pořadí práce

### 1. Detail týmu a klubu

Priorita:

- `teaminfo` -> `team`
- `clubinfo` -> `club`

Důvod: jde o základní informační stránky, na které budou odkazovat rozpisy, výsledky, tabulky i moduly.

Přenést chování:

- informace o týmu a klubu,
- klubová vazba týmu,
- sezóny týmu,
- počet hráčů v projektu,
- hřiště klubu,
- adresa klubu,
- odkazy na související týmy a projekty.

### 2. Rozpisy podle týmu a klubu

Priorita:

- `teamplan` -> `schedule`
- `clubplan` -> `schedule`

Důvod: rozpis je hlavní veřejný výstup soutěže. Stará komponenta měla samostatné pohledy, v6 má lepší cíl ve společném `schedule`.

Přenést chování:

- filtr podle `team_id`,
- filtr podle `club_id`,
- domácí/venkovní zápasy,
- rozsah dat,
- řazení podle kola nebo data,
- volitelné zobrazení rozhodčích a hřiště.

### 3. Nejbližší zápas a detail zápasu

Priorita:

- `nextmatch` -> `matchreport` nebo nový `nextmatch`

Důvod: výstup je vhodný pro moduly i detailní frontend stránku.

Přenést chování:

- nalezení konkrétního nebo nejbližšího zápasu,
- domácí a hostující tým,
- rozhodčí,
- pořadí týmů v tabulce před zápasem,
- historické zápasy mezi týmy,
- základní statistiky před zápasem.

### 4. Výsledkové matice a kombinované stránky

Priorita:

- `matrix`
- `resultsmatrix`
- `resultsranking`

Důvod: jde o pokročilejší soutěžní výstupy. Navazují na stabilní výsledky a tabulku.

Přenést chování:

- matice zápasů tým proti týmu,
- filtr podle divize,
- filtr podle kola,
- kombinace výsledků a tabulky na jedné stránce.

### 5. Statistiky a pořadí

Priorita:

- `statsranking` -> `stats`
- `teamstats` -> `stats` nebo `team`
- `eventsranking` -> `stats`

Důvod: statistiky potřebují sjednotit datovou logiku hráčů, týmů, událostí a pozic.

Přenést chování:

- pořadí hráčů podle vybrané statistiky,
- pořadí hráčů podle událostí,
- týmová maxima a minima,
- filtry podle týmu, divize a projektu,
- stránkování.

### 6. Speciální výstupy

Priorita:

- `rivals`
- `treetonode`
- `ical`
- `curve`

Důvod: mají užší použití a mohou počkat, dokud jsou stabilní hlavní soutěžní výstupy.

Poznámky:

- `rivals` má zůstat týmový pohled na soupeře a vzájemné zápasy.
- `treetonode` patří k turnajovým stromům.
- `ical` má vzniknout až nad novým `schedule`, ne jako izolovaný port staré logiky.
- `curve` je historicky grafový výstup a nemá se přenášet se starou grafovou knihovnou.

### 7. Tipovací soutěž

Priorita:

- tipovací soutěž jako navazující core oblast

Důvod: stará JoomLeague tipovací soutěž obsahovala jako součást hlavního core. Ve v6 se k ní má vrátit až po stabilizaci základních výstupů soutěží, rozpisů, výsledků, tabulek a statistik.

Přenést nebo znovu navrhnout:

- vazbu tipů na zápasy,
- vazbu na Joomla uživatele,
- pravidla bodování,
- uzávěrky tipování,
- přehled pořadí tipujících,
- administraci soutěže a pravidel,
- frontend pro zadání tipů,
- ochranu proti změně tipů po uzávěrce.

## Implementační pravidla

- Vstupy číst přes Joomla input API a typované metody.
- Databázové dotazy psát přes query builder.
- Hodnoty bindovat, ne skládat do SQL řetězců.
- Výstup v šablonách escapovat.
- Opakovanou soutěžní logiku držet v modelech nebo službách, ne v `tmpl`.
- Staré helpery nepřenášet celé; přebírat jen potřebné chování.
- Nové view přidat jen tam, kde nejde přirozeně rozšířit existující v6 view.
- Každý přenesený výstup musí mít minimálně základní prázdný stav bez PHP warningů.
- Po změně spustit validátory a sestavení balíčku.

## Nejbližší pracovní balík

První balík pro implementaci:

```text
teaminfo -> team
clubinfo -> club
```

Konkrétní kroky:

1. Porovnat staré modely `teaminfo.php` a `clubinfo.php` s v6 `TeamModel.php` a `ClubModel.php`.
2. Doplnit chybějící datové metody do v6 modelů.
3. Rozšířit šablony `tmpl/team/default.php` a `tmpl/club/default.php`.
4. Doplnit chybějící jazykové klíče pro EN/CZ.
5. Sestavit balíček.
6. Otestovat na demo instalaci.
