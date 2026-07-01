# Release postup

## Před releasem

1. Zkontrolovat verzi v `pkg_joomleague.xml`.
2. Zkontrolovat verzi v `administrator/components/com_joomleague/joomleague.xml`.
3. Aktualizovat `CHANGELOG.md`.
4. Spustit build:

```bash
python3 build/package.py
```

5. Ověřit vznik finálního ZIPu:

```text
dist/pkg_joomleague-<verze>.zip
```

## Doporučený release tag

Používat tag ve formátu:

```text
v0.20.10
```

## GitHub release

K releasu přiložit hlavní Joomla instalační ZIP:

```text
dist/pkg_joomleague-<verze>.zip
```

Do release notes stručně uvést:

- změny v komponentě,
- změny v modulech,
- změny v pluginech,
- databázové změny,
- případné migrační poznámky.

