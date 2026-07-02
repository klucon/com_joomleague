#!/usr/bin/env python3
"""Build JoomLeague package ZIP for Joomla installer.

The source tree keeps the component, modules and plugins unpacked so they can be
reviewed and tested independently. Joomla package installation requires child
extensions as ZIP archives referenced by pkg_joomleague.xml, so this script
creates:

dist/packages/com_joomleague.zip
dist/packages/mod_*.zip
dist/packages/plg_*.zip
dist/pkg_joomleague-<version>.zip
"""

from __future__ import annotations

import shutil
import xml.etree.ElementTree as ET
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile


ROOT = Path(__file__).resolve().parents[1]
DIST = ROOT / "dist"
PACKAGES = DIST / "packages"
PACKAGE_MANIFEST = ROOT / "pkg_joomleague.xml"
COMPONENT_MANIFEST = ROOT / "administrator/components/com_joomleague/joomleague.xml"

COMPONENT_INCLUDE = (
    "administrator",
    "components",
    "images",
    "media",
    "script.php",
)

EXCLUDE_NAMES = {
    ".git",
    "build",
    "dist",
    "modules",
    "plugins",
    "pkg_joomleague.xml",
}


def version() -> str:
    return ET.parse(PACKAGE_MANIFEST).getroot().findtext("version", "0.0.0")


def zip_path(source: Path, target: Path, base: Path) -> None:
    with ZipFile(target, "w", ZIP_DEFLATED) as archive:
        if source.is_file():
            archive.write(source, source.relative_to(base).as_posix())
            return

        for file in sorted(source.rglob("*")):
            if not file.is_file():
                continue

            archive.write(file, file.relative_to(base).as_posix())


def build_component() -> None:
    target = PACKAGES / "com_joomleague.zip"

    with ZipFile(target, "w", ZIP_DEFLATED) as archive:
        archive.write(COMPONENT_MANIFEST, "joomleague.xml")

        for include in COMPONENT_INCLUDE:
            source = ROOT / include

            if not source.exists():
                raise RuntimeError(f"Missing component source: {source}")

            if source.is_file():
                archive.write(source, source.relative_to(ROOT).as_posix())
                continue

            for file in sorted(source.rglob("*")):
                if file.is_file():
                    archive.write(file, file.relative_to(ROOT).as_posix())


def build_modules() -> None:
    for module_dir in sorted((ROOT / "modules").glob("mod_*")):
        if not module_dir.is_dir():
            continue

        zip_path(module_dir, PACKAGES / f"{module_dir.name}.zip", module_dir)


def build_plugins() -> None:
    for plugin_dir in sorted((ROOT / "plugins").glob("*/*")):
        if not plugin_dir.is_dir():
            continue

        group = plugin_dir.parent.name
        name = plugin_dir.name
        zip_path(plugin_dir, PACKAGES / f"plg_{group}_{name}.zip", plugin_dir)


def build_package() -> Path:
    package_zip = DIST / f"pkg_joomleague-{version()}.zip"

    with ZipFile(package_zip, "w", ZIP_DEFLATED) as archive:
        archive.write(PACKAGE_MANIFEST, "pkg_joomleague.xml")
        archive.write(ROOT / "pkg_script.php", "pkg_script.php")

        for file in sorted((ROOT / "language").rglob("*.ini")):
            archive.write(file, file.relative_to(ROOT).as_posix())

        for package in sorted(PACKAGES.glob("*.zip")):
            archive.write(package, f"packages/{package.name}")

    return package_zip


def main() -> None:
    if not COMPONENT_MANIFEST.exists():
        raise RuntimeError(f"Missing component manifest: {COMPONENT_MANIFEST}")

    shutil.rmtree(DIST, ignore_errors=True)
    PACKAGES.mkdir(parents=True, exist_ok=True)

    build_component()
    build_modules()
    build_plugins()
    package_zip = build_package()

    print(package_zip)


if __name__ == "__main__":
    main()
