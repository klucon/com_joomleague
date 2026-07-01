# Joomla update server

Update server bude plně provozovaný přes GitHub.

## Cílová architektura

- ZIP balíčky budou uložené jako GitHub Release assets.
- Joomla update feed bude publikovaný přes GitHub Pages.
- Joomla changelog XML bude publikovaný přes GitHub Pages.
- Release workflow bude automaticky sestavovat balíček a připravovat release.

## Veřejné URL

Update feed:

```text
https://klucon.github.io/com_joomleague/updates/joomleague.xml
```

Changelog:

```text
https://klucon.github.io/com_joomleague/updates/changelog.xml
```

Release asset pro `0.30.0`:

```text
https://github.com/klucon/com_joomleague/releases/download/v0.30.0/pkg_joomleague-0.30.0.zip
```

## Package manifest

Do `pkg_joomleague.xml` se po synchronizaci zdrojů přidá update server a changelog URL:

```xml
<updateservers>
	<server type="extension" priority="1" name="JoomLeague Updates">https://klucon.github.io/com_joomleague/updates/joomleague.xml</server>
</updateservers>

<changelogurl>https://klucon.github.io/com_joomleague/updates/changelog.xml</changelogurl>
```

## Bridge verze 0.21.50

`0.21.50` je soukromá přechodová verze pro existující Joomla 6 instalaci.

Musí obsahovat:

- update server URL,
- changelog URL,
- stejný package element `pkg_joomleague`.

Instaluje se ručně přes Joomla administraci. Jejím cílem je připravit instalaci na update na `0.30.0`.

## Release verze 0.30.0

`0.30.0` je první veřejný GitHub release.

Update feed musí ukazovat na:

```text
pkg_joomleague-0.30.0.zip
```

Joomla musí rozpoznat update:

```text
0.21.50 -> 0.30.0
```

## Automatizace

Release workflow po tagu `v0.30.0` má:

1. načíst verzi z manifestu,
2. ověřit, že tag odpovídá manifest verzi,
3. sestavit Joomla package,
4. vytvořit GitHub Release,
5. přiložit ZIP asset,
6. vygenerovat update feed,
7. vygenerovat changelog XML,
8. publikovat změny přes GitHub Pages.

## Test

1. Nainstalovat `0.21.50` ručně do existující Joomla 6 instalace.
2. Ověřit, že Joomla zná update server.
3. Publikovat `0.30.0`.
4. Spustit hledání aktualizací v Joomla.
5. Ověřit nabídku update na `0.30.0`.
6. Ověřit zobrazení changelogu.
7. Provést update.
8. Ověřit nainstalovanou verzi `0.30.0`.

