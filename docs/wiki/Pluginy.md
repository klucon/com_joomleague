# Pluginy

Balíček obsahuje integrační pluginy:

- `content/joomleaguematch`
- `content/joomleagueperson`
- `extension/joomleagueesport`
- `finder/joomleague`
- `quickicon/joomleague`

## Umístění zdrojů

```text
plugins/<group>/<name>/
```

Při buildu se každý plugin balí samostatně do:

```text
dist/packages/plg_<group>_<name>.zip
```

## Automatické zapnutí

Instalační skript balíčku při první instalaci pluginy automaticky zapne. Při aktualizaci už jejich stav nemění.

