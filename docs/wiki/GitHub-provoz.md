# GitHub provoz

Tato stránka popisuje provozní nastavení repozitáře `klucon/com_joomleague`.

## Nastavení repozitáře

- Wiki je zapnutá.
- Issues jsou zapnuté.
- Projects jsou zapnuté.
- Merge do `main` je nastavený přes squash merge.
- Merge commit a rebase merge jsou vypnuté.
- Branch se po merge automaticky maže.
- `main` je chráněná proti force push a smazání.

## Automatizace

### Build Joomla package

Workflow:

```text
.github/workflows/build-package.yml
```

Spouští se při pushi do `main` a při pull requestech. Ověřuje, že jde sestavit Joomla package přes:

```bash
python3 build/package.py
```

### Release Joomla package

Workflow:

```text
.github/workflows/release-package.yml
```

Spouští se pouze při pushi tagu:

```text
v*
```

Vytvoří Joomla ZIP a přiloží jej k GitHub Release.

Po doplnění update serveru bude release workflow rozšířené také o generování Joomla update feedu a changelog XML pro GitHub Pages.

## První synchronizace zdrojů

První synchronizace zdrojů je záměrně blokovaná, dokud nebude dokončena aktuální lokální práce mimo tento proces.

Checklist je vedený v issue:

```text
https://github.com/klucon/com_joomleague/issues/1
```

Po importu zdrojů je potřeba:

1. spustit lokálně `python3 build/package.py`,
2. ověřit GitHub Actions build,
3. připravit bridge balíček `0.21.50`,
4. nastavit Joomla update server a changelog URL,
5. publikovat první veřejný release `0.30.0`,
6. otestovat update `0.21.50 -> 0.30.0`,
7. nastavit povinný passing check pro `main`.

## Aktuální milník

Aktuální plán je vedený v issue:

```text
https://github.com/klucon/com_joomleague/issues/1
```

Milník:

```text
0.30.0 first GitHub release
```
