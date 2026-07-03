# Komponenta

Komponenta `com_joomleague` je hlavní část balíčku.

## Namespace

```text
Joomleague\Component\Joomleague
```

## Administrace

Administrační menu obsahuje:

- Přehled
- Projekty
- Druhy sportu
- Ligy
- Sezóny
- Kluby
- Týmy
- Osoby
- Typy událostí
- Statistiky
- Pozice
- Stadiony
- Nástroje

## Frontend pohledy

Frontendová část poskytuje pohledy pro:

- projekty,
- týmy,
- kluby,
- osoby,
- hřiště,
- rozpis,
- výsledky,
- tabulky,
- statistiky,
- soupisky,
- rozhodčí,
- detail zápasu.

Pracovní mapa přepisu starších frontendových výstupů je v dokumentu [[Frontend-prepis]].

## Databáze

Komponenta používá tabulky s prefixem:

```text
#__joomleague_
```

Hlavní oblasti datového modelu:

- kluby a týmy,
- osoby, hráči, členové realizačních týmů a rozhodčí,
- ligy, sezóny, projekty a kola,
- zápasy, události a statistiky,
- pozice, stadiony a hřiště,
- turnajové stromy,
- šablony konfigurace.
