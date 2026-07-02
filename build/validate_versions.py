#!/usr/bin/env python3
"""Validate release version consistency."""

from __future__ import annotations

import re
import sys
import xml.etree.ElementTree as ET
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PACKAGE_MANIFEST = ROOT / "pkg_joomleague.xml"
COMPONENT_MANIFEST = ROOT / "administrator/components/com_joomleague/joomleague.xml"
README = ROOT / "README.md"
CHANGELOG = ROOT / "CHANGELOG.md"


def manifest_version(path: Path) -> str:
    version = ET.parse(path).getroot().findtext("version")

    if not version:
        raise RuntimeError(f"Missing version in {path.relative_to(ROOT)}")

    return version.strip()


def main() -> int:
    package_version = manifest_version(PACKAGE_MANIFEST)
    component_version = manifest_version(COMPONENT_MANIFEST)
    errors: list[str] = []

    if component_version != package_version:
        errors.append(
            "Component manifest version does not match package manifest version: "
            f"{component_version} != {package_version}"
        )

    readme = README.read_text(encoding="utf-8")
    if f"Aktuální verze: `{package_version}`" not in readme:
        errors.append(f"README.md does not contain current version {package_version}")

    changelog = CHANGELOG.read_text(encoding="utf-8")
    if not re.search(rf"^##\s+{re.escape(package_version)}\s+-\s+\d{{4}}-\d{{2}}-\d{{2}}\s*$", changelog, re.MULTILINE):
        errors.append(f"CHANGELOG.md does not contain a dated section for {package_version}")

    if errors:
        for error in errors:
            print(f"ERROR: {error}", file=sys.stderr)

        return 1

    print(package_version)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
