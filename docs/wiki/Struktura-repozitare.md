# Struktura repozitáře

```text
administrator/components/com_joomleague/  Administrační část komponenty
components/com_joomleague/                Frontendová část komponenty
modules/                                  Frontendové Joomla moduly
plugins/                                  Joomla pluginy
language/                                 Jazykové soubory balíčku
media/com_joomleague/                     Media assety komponenty
build/package.py                          Build skript instalačního ZIPu
pkg_joomleague.xml                        Manifest Joomla balíčku
pkg_script.php                            Instalační skript balíčku
script.php                                Instalační skript komponenty
```

## Výstupy

Adresář `dist/` je build výstup a nemá se verzovat.

## Manifesty

- `pkg_joomleague.xml` popisuje celý Joomla package.
- `administrator/components/com_joomleague/joomleague.xml` popisuje komponentu.
- Každý modul má vlastní `mod_joomleague_*.xml`.
- Každý plugin má vlastní XML manifest ve své složce.

