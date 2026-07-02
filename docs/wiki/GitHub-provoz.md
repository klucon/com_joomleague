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
python3 build/validate_versions.py
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

Vytvoří Joomla ZIP, vygeneruje update feed a changelog XML a přiloží vše k GitHub Release.

## První synchronizace zdrojů

První synchronizace zdrojů proběhla v commitu:

```text
154a17f3c1bd0a8efe94a751ed8a643cd3371162
```

Checklist je vedený v issue:

```text
https://github.com/klucon/com_joomleague/issues/1
```

Po importu zdrojů zbývá:

1. připravit bridge balíček `0.21.50`,
2. publikovat první veřejný release `0.30.0`,
3. otestovat update `0.21.50 -> 0.30.0`,
4. nastavit povinný passing check pro `main`.

## Aktuální milník

Aktuální plán je vedený v issue:

```text
https://github.com/klucon/com_joomleague/issues/1
```

Milník:

```text
0.30.0 first GitHub release
```
