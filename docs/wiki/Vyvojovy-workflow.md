# Vývojový workflow

## Branching

1. Vycházet z `main`.
2. Pro změnu vytvořit samostatnou feature branch.
3. Změnu poslat přes pull request.
4. Merge do `main` dělat přes squash merge.

## Před pull requestem

Spustit build:

```bash
python3 build/package.py
```

Zkontrolovat:

- že se vytvořil `dist/pkg_joomleague-<verze>.zip`,
- že se nezměnil build výstup v gitu,
- že manifesty odpovídají reálným souborům,
- že jazykové klíče existují pro `cs-CZ` i `en-GB`, pokud se měnil text v UI.

## GitHub nastavení

Repozitář používá:

- squash merge,
- automatické mazání branch po merge,
- chráněnou větev `main` proti force push a smazání,
- GitHub Actions build workflow,
- Dependabot pro GitHub Actions.

