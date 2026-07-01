# Release postup

První veřejný release na GitHubu bude `0.30.0`. Před ním vznikne soukromý bridge balíček `0.21.50`, který se ručně nainstaluje do existující Joomla 6 instalace a doplní do ní update server a changelog URL.

## Verze

- `0.21.50` - soukromý bridge balíček pro existující instalaci.
- `0.30.0` - první veřejný GitHub release a první ostrý test Joomla update serveru.

## Před releasem

1. Zkontrolovat verzi v `pkg_joomleague.xml`.
2. Zkontrolovat verzi v `administrator/components/com_joomleague/joomleague.xml`.
3. Aktualizovat `CHANGELOG.md`.
4. Aktualizovat Joomla changelog XML.
5. Spustit build:

```bash
python3 build/package.py
```

6. Ověřit vznik finálního ZIPu:

```text
dist/pkg_joomleague-<verze>.zip
```

## Bridge balíček 0.21.50

Bridge balíček není veřejný milník. Slouží jen k tomu, aby existující Joomla 6 instalace dostala:

- update server URL,
- changelog URL.

Postup:

1. Nastavit verzi manifestů na `0.21.50`.
2. Přidat update server a changelog URL do package manifestu.
3. Sestavit `pkg_joomleague-0.21.50.zip`.
4. ZIP ručně nainstalovat přes Joomla administraci.
5. Ověřit, že Joomla eviduje update server.

## Veřejný release 0.30.0

První veřejný release bude tag:

```text
v0.30.0
```

## GitHub release

Release workflow sestaví a přiloží hlavní Joomla instalační ZIP:

```text
dist/pkg_joomleague-<verze>.zip
```

Pro `0.30.0` musí zároveň vzniknout nebo být aktualizováno:

- GitHub Release asset `pkg_joomleague-0.30.0.zip`,
- Joomla update feed,
- Joomla changelog XML.

Do release notes stručně uvést:

- změny v komponentě,
- změny v modulech,
- změny v pluginech,
- databázové změny,
- případné migrační poznámky.

## Joomla update test

Po publikování `0.30.0`:

1. V Joomla instalaci s `0.21.50` vyčistit update cache, pokud bude potřeba.
2. Spustit hledání aktualizací rozšíření.
3. Ověřit, že Joomla nabízí update na `0.30.0`.
4. Ověřit, že Joomla zobrazuje changelog.
5. Provest update přes Joomla updater.
6. Ověřit, že nainstalovaná verze je `0.30.0`.
