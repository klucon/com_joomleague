# JoomLeague 6.x – moduly a pluginy

Tento dokument popisuje doplňková rozšíření dodávaná společně s komponentou JoomLeague 6.x.

Instalační strategie:

- JoomLeague 6.x se instaluje pouze jako kompletní Joomla package.
- Package obsahuje komponentu, všechny moduly a všechny pluginy.
- Package manifest je `pkg_joomleague.xml`.
- Component-only instalace není cílový instalační režim.
- Root component manifest v kořeni zdrojové složky se nepoužívá; komponentní manifest je uvnitř `administrator/components/com_joomleague/joomleague.xml`.
- Package ZIP se vytváří skriptem `build/package.py`.
- Child extensions jsou uvnitř package uloženy ve složce `packages/`.

## Site moduly

| Modul | Účel | Stav |
|---|---|---|
| `mod_joomleague_birthday` | Nadcházející narozeniny osob v projektu. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_calendar` | Přehled zápasů v kalendářním/časovém výpisu. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_eventsranking` | Žebříček osob podle vybraného typu události. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_logo` | Zobrazení loga/týmů z projektu. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_matches` | Výpis zápasů, výsledků nebo nadcházejících utkání. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_navigation_menu` | Navigace v rámci projektu JoomLeague. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_playgroundplan` | Přehled stadionů/hřišť. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_randomplayer` | Náhodný hráč projektu/týmu. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_ranking` | Tabulka projektu. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_results` | Výsledky projektu. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_sports_type_statistics` | Souhrn statistik podle druhu sportu. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_statranking` | Žebříček osob podle statistik. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_teamplayers` | Seznam hráčů týmu. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_teamstaffs` | Seznam realizačního týmu. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_teamstats_ranking` | Týmový žebříček podle statistik. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |
| `mod_joomleague_ticker` | Krátký ticker zápasů/výsledků. | J6 struktura, DI dispatcher, helper, šablona, jazyky. |

## Pluginy

| Plugin | Skupina | Účel | Stav |
|---|---|---|---|
| `joomleagueperson` | `content` | Obsahový plugin pro vkládání odkazu/dat osoby JoomLeague do článků. | J6 struktura, DI provider, jazyky. |
| `joomleagueesport` | `extension` | Extension lifecycle plugin pro doprovodné operace JoomLeague/eSport. | J6 struktura, DI provider, jazyky. |
| `joomleague` | `finder` | Integrace JoomLeague obsahu do Smart Search. | J6 struktura, DI provider, jazyky. |
| `joomleague` | `quickicon` | Rychlá ikona do Joomla administrace. | J6 struktura, DI provider, jazyky. |

## Kontroly

- Všechny XML manifesty modulů a pluginů jsou parsovatelné.
- Všechny PHP soubory modulů a pluginů prošly `php -l` v Joomla/PHP kontejneru.
- Jazykové soubory `cs-CZ` a `en-GB` jsou syntakticky parsovatelné.
- Moduly a pluginy se neinstalují přes component manifest; instaluje je package manifest.

## Build

Vytvoření instalačního package ZIPu:

```bash
cd /mnt/disk-a/dev/com_joomleague_v6
python3 build/package.py
```

Výstup:

- `dist/packages/com_joomleague.zip`
- `dist/packages/mod_*.zip`
- `dist/packages/plg_*.zip`
- `dist/pkg_joomleague-<version>.zip`
