# Tipovací soutěž

Tipovací soutěž je core oblast JoomLeague. Cílem je navázat na původní funkci staré JoomLeague, ale přepsat ji do struktury Joomla 6.

## Milník

Plně dokončená tipovací soutěž patří do verze `0.40.0`. Předchozí verze mohou obsahovat dílčí kroky, ale nemají být prezentované jako kompletní tipovací soutěž.

## Aktuální stav

Aktuální implementace obsahuje použitelný základ soutěže:

- databázové tabulky pro soutěž, tipy a bodové souhrny,
- upgrade SQL pro existující instalace,
- uninstall úklid nových tabulek,
- administrace soutěží v nástrojích komponenty,
- napojení z panelu projektu,
- administrační přehled uložených tipů,
- administrační přehled bodových souhrnů,
- ruční přepočet bodů z administrace,
- základní pravidla bodování a uzávěrka tipování,
- frontend pro zadávání tipů přihlášeným uživatelům,
- uzamčení tipů podle času výkopu a nastavené uzávěrky,
- přepočet bodů podle zadaných výsledků,
- pořadí tipujících,
- filtrování tipů a pořadí podle kola.

## Datový model

| Tabulka | Účel |
| --- | --- |
| `#__joomleague_prediction_game` | nastavení tipovací soutěže pro projekt |
| `#__joomleague_prediction_tip` | tip uživatele na konkrétní zápas |
| `#__joomleague_prediction_score` | souhrn bodů uživatele za soutěž a kolo |

## Další kroky

1. Ověřit chování v reálné Joomla instalaci s více uživateli.
2. Doplnit volitelný detail tipů jednotlivých uživatelů podle oprávnění.
3. Rozšířit pořadí o detail po kolech, pokud bude potřeba samostatná podstránka mimo filtr.

## Pravidla bodování

Základní administrace počítá s těmito volbami:

- body za přesný výsledek,
- body za správnou tendenci výsledku,
- body za správný rozdíl skóre,
- volitelně viditelné pořadí tipujících.

Vyhodnocení je exkluzivní. Nejprve se kontroluje přesný výsledek, potom správný rozdíl skóre a nakonec správná tendence. Body se mezi kategoriemi nesčítají.
