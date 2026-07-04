# JoomLeague pro Joomla 6

Kompletní balíček JoomLeague 6.x pro správu lig a sportovních soutěží v Joomla 6.

Balíček obsahuje hlavní komponentu `com_joomleague`, sadu frontendových modulů a integrační pluginy. Zdrojový strom drží jednotlivá rozšíření rozbalená kvůli vývoji a kontrole; instalační ZIP pro Joomla se sestavuje skriptem `build/package.py`.

## Stav balíčku

- Název balíčku: `pkg_joomleague`
- Aktuální verze: `0.40.3`
- Cílová platforma: Joomla 6
- Licence: GNU GPL v2 nebo novější
- Jazyky: čeština (`cs-CZ`), angličtina (`en-GB`) a němčina (`de-DE`)

## Co komponenta řeší

JoomLeague je systém pro správu sportovních soutěží. Administrace obsahuje datové modely a obrazovky pro:

- druhy sportu, ligy a sezóny,
- projekty soutěží,
- kluby, týmy a osoby,
- hráče, realizační týmy a rozhodčí,
- kola, zápasy, výsledky a soupisky,
- typy událostí, pozice a statistiky,
- hřiště/stadiony,
- turnajové stromy a šablony konfigurace.

Frontendová část poskytuje pohledy pro projekty, týmy, kluby, osoby, hřiště, rozpis, výsledky, tabulky, statistiky, soupisky, rozhodčí a detail zápasu.

## Obsah instalačního balíčku

### Komponenta

- `com_joomleague`

### Moduly

- `mod_joomleague_birthday`
- `mod_joomleague_calendar`
- `mod_joomleague_eventsranking`
- `mod_joomleague_logo`
- `mod_joomleague_matches`
- `mod_joomleague_navigation_menu`
- `mod_joomleague_playgroundplan`
- `mod_joomleague_randomplayer`
- `mod_joomleague_ranking`
- `mod_joomleague_results`
- `mod_joomleague_sports_type_statistics`
- `mod_joomleague_statranking`
- `mod_joomleague_teamplayers`
- `mod_joomleague_teamstaffs`
- `mod_joomleague_teamstats_ranking`
- `mod_joomleague_ticker`

### Pluginy

- `content/joomleaguematch`
- `content/joomleagueperson`
- `extension/joomleagueesport`
- `finder/joomleague`
- `quickicon/joomleague`

Při první instalaci balíčku se vlastní pluginy automaticky zapnou. Při aktualizaci se respektuje aktuální nastavení uživatele.

## Instalace

1. Stáhněte nebo sestavte instalační ZIP balíčku `pkg_joomleague-<verze>.zip`.
2. V administraci Joomla otevřete `System -> Install -> Extensions`.
3. Nahrajte ZIP balíček.
4. Po instalaci otevřete komponentu `JoomLeague` v administračním menu.

Aktuálně sestavený artefakt je ukládán do:

```text
dist/pkg_joomleague-<verze>.zip
```

## Sestavení balíčku

Požadavek pro build je Python 3.

```bash
python3 build/package.py
```

Skript vytvoří:

- `dist/packages/com_joomleague.zip`
- `dist/packages/mod_*.zip`
- `dist/packages/plg_*.zip`
- `dist/pkg_joomleague-<verze>.zip`

Verze balíčku se čte z `pkg_joomleague.xml`.

## Struktura repozitáře

```text
administrator/components/com_joomleague/  Administrační část komponenty
components/com_joomleague/                Frontendová část komponenty
modules/                                  Frontendové Joomla moduly
plugins/                                  Joomla pluginy
language/                                 Jazykové soubory balíčku
media/com_joomleague/                     Media assety komponenty
build/package.py                          Build skript instalačního ZIPu
pkg_joomleague.xml                        Manifest Joomla balíčku
pkg_script.php                            Instalační skript balíčku
script.php                                Instalační skript komponenty
```

## Databáze

Instalace komponenty zakládá tabulky s prefixem `#__joomleague_`. Hlavní oblasti datového modelu jsou kluby, týmy, osoby, projekty, ligy, sezóny, kola, zápasy, události, statistiky, pozice, hřiště, turnajové stromy a šablony konfigurace.

SQL schémata jsou v:

```text
administrator/components/com_joomleague/sql/
```

## Vývojové poznámky

- Zdrojové složky modulů a pluginů zůstávají rozbalené v `modules/` a `plugins/`.
- Joomla balíček očekává dětská rozšíření jako ZIP soubory v `packages/`; ty se generují až při buildu.
- Build adresář `dist/` je výstup a lze jej kdykoliv znovu vytvořit.
- Komponenta používá namespacing `Joomleague\Component\Joomleague`.
- Administrační menu zahrnuje přehled, projekty, druhy sportu, ligy, sezóny, kluby, týmy, osoby, typy událostí, statistiky, pozice, stadiony a nástroje.
