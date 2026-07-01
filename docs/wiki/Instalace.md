# Instalace

## Instalace přes Joomla administraci

1. Sestavte nebo stáhněte ZIP balíček `pkg_joomleague-<verze>.zip`.
2. V Joomla administraci otevřete `System -> Install -> Extensions`.
3. Nahrajte ZIP balíček.
4. Po dokončení otevřete komponentu `JoomLeague` v administračním menu.

## Instalační artefakt

Build vytváří hlavní instalační balíček:

```text
dist/pkg_joomleague-0.20.10.zip
```

Tento ZIP obsahuje:

- komponentu `com_joomleague`,
- všechny moduly `mod_joomleague_*`,
- pluginy `plg_*_joomleague*`,
- jazykové soubory balíčku,
- instalační skript `pkg_script.php`.

## Pluginy po instalaci

Při první instalaci balíčku se vlastní pluginy automaticky zapnou:

- `content/joomleaguematch`
- `content/joomleagueperson`
- `extension/joomleagueesport`
- `finder/joomleague`
- `quickicon/joomleague`

Při aktualizaci se respektuje aktuální nastavení uživatele.

