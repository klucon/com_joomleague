# Sestavení balíčku

Build zajišťuje skript:

```bash
python3 build/package.py
```

## Co build vytvoří

```text
dist/packages/com_joomleague.zip
dist/packages/mod_*.zip
dist/packages/plg_*.zip
dist/pkg_joomleague-<verze>.zip
```

Verze se čte z:

```text
pkg_joomleague.xml
```

## Princip buildu

Zdrojový strom drží komponentu, moduly a pluginy rozbalené. Joomla package instalátor ale očekává dětská rozšíření jako ZIP soubory referenced v `pkg_joomleague.xml`.

Skript proto:

1. smaže a znovu vytvoří `dist/`,
2. zabalí komponentu jako `com_joomleague.zip`,
3. zabalí každý modul z `modules/mod_*`,
4. zabalí každý plugin z `plugins/<group>/<name>`,
5. vytvoří finální package ZIP `pkg_joomleague-<verze>.zip`.

## CI

GitHub Actions workflow `.github/workflows/build-package.yml` spouští build při pushi do `main` a při pull requestech.

