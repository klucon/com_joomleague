#!/usr/bin/env python3
"""Validate generated Joomla package contents."""

from __future__ import annotations

import sys
import xml.etree.ElementTree as ET
from pathlib import Path
from zipfile import ZipFile


ROOT = Path(__file__).resolve().parents[1]
DIST = ROOT / "dist"
PACKAGE_MANIFEST = ROOT / "pkg_joomleague.xml"


def package_version() -> str:
    version = ET.parse(PACKAGE_MANIFEST).getroot().findtext("version")

    if not version:
        raise RuntimeError("Missing package version")

    return version


def expected_child_packages() -> list[str]:
    root = ET.parse(PACKAGE_MANIFEST).getroot()
    files = root.find("files")

    if files is None:
        raise RuntimeError("Package manifest is missing files section")

    return sorted(file.text or "" for file in files.findall("file"))


def main() -> int:
    version = package_version()
    package = DIST / f"pkg_joomleague-{version}.zip"
    errors: list[str] = []

    if not package.exists():
        errors.append(f"Missing package ZIP: {package.relative_to(ROOT)}")
    else:
        with ZipFile(package) as archive:
            names = set(archive.namelist())

            for required in ["pkg_joomleague.xml", "pkg_script.php"]:
                if required not in names:
                    errors.append(f"Package ZIP is missing {required}")

            for child in expected_child_packages():
                if f"packages/{child}" not in names:
                    errors.append(f"Package ZIP is missing packages/{child}")

            manifest = ET.fromstring(archive.read("pkg_joomleague.xml"))
            manifest_version = manifest.findtext("version")
            changelog_url = manifest.findtext("changelogurl")
            update_servers = manifest.find("updateservers")

            if manifest_version != version:
                errors.append(f"Package ZIP manifest version is {manifest_version}, expected {version}")

            if not changelog_url:
                errors.append("Package ZIP manifest is missing changelogurl")

            if update_servers is None or update_servers.find("server") is None:
                errors.append("Package ZIP manifest is missing update server")

    for metadata in [DIST / "joomleague-update.xml", DIST / "joomleague-changelog.xml"]:
        if not metadata.exists():
            errors.append(f"Missing release metadata: {metadata.relative_to(ROOT)}")

    if errors:
        for error in errors:
            print(f"ERROR: {error}", file=sys.stderr)

        return 1

    print(package)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
