# Language Distribution Contract

## Source And Package Are Different Inventories

Effective RC2, by owner decision on 2026-10-25, English (`en-GB`) is the only
language in the base package, component, modules and plugins, including the
Countries connector. Czech (`cs-CZ`), German, Polish and other translations are
independent language packages maintained through https://translate.joomleague.eu/.
Translation source directories are preserved, not deleted. Their presence does
not permit bundling them or declaring them in base manifests. The base release
no longer requires Czech source completeness/key-order parity.

The build discovers non-bundled locale directories and excludes their root,
component-admin and component-site paths from every output ZIP. Source files are
not deleted or rewritten. All module archives are rebuilt from scratch, including
Birthdays, Spotlight and Latest Results, so old entries cannot survive a ZIP update.

`tests/Architecture/verify-package-languages.cjs` reads the actual outer package
and all 23 nested extensions plus the Countries dependency with native XML
parsing. It requires exactly the declared en-GB payload, checks every file
exists and is nonempty, rejects
undeclared translations and locale directories, missing files, duplicate members
and unsafe paths. The build runs this gate after assembling the full package.
The current inventory is 25 archives and 59 declared English INI files.

Source architecture checks require the English files and guard their keys and
duplicates. Translation completeness belongs to the separate translation pipeline.
English manifests still declare runtime, menu and guided-tour files so Joomla
updates both component-local and global installed copies. Existing installed
translations and user overrides are not removed by custom cleanup code.

RC2 verification: `docs/review/2026-10-25-english-only-rc2.md`.

## Historical Installation And Upgrade Evidence (Superseded Policy)

The bilingual counts below describe the 2026-10-21 audit only, not current RC2.

Test-only archives are in `tests/artifacts/language-package-audit-20261021/`.
Their manifest version stays `6.2.0-rc1` for local acceptance; these are not a new
public release or replacements for the published RC1 download.

`tests/artifacts/package-mvhx12oc/` contains eight passing mandatory package
observations for MariaDB and PostgreSQL, including native schema checks. On each
driver, fresh English installation validates 59 physical files. An update replaces
118 deliberately stale global/local English/Czech files, preserves three separately
maintained translation fixtures and two user override files, preserves sports data,
and retains native package ownership and the registered update server.

The clean Joomla fixture starts with core English only. Joomla intentionally skips
global translation destinations whose language directory does not exist. The test
creates Czech destination folders and stale files before the upgrade, exercising
the native copy rule without claiming a full Czech core language pack was installed
or a Czech UI browser session was tested. Joomla core language installation remains
separate from JoomLeague translations.

The inventory mutation test rejects six malformed real ZIP variants: separate
languages in outer/nested archives, a missing declared file, an undeclared English
file, an unbundled manifest tag and a path-traversal declaration. The full foundation
audit, release gate and component-manifest unit now pass. The manifest unit ran in
a network-isolated PHP runtime with SimpleXML because the host PHP lacks it.

## Boundaries

No production deployment, publication, language feed update or Weblate changes
were performed. These tests do not certify the nightly translation job, independent
language-package uninstall/updates, browser ZIP upload or a remote Joomla updater
download. Those remain separate acceptance workflows.
