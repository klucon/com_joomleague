#!/usr/bin/env python3
"""Generate Joomla update and changelog XML files for a release."""

from __future__ import annotations

import argparse
import re
import xml.dom.minidom
import xml.etree.ElementTree as ET
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
DIST = ROOT / "dist"
PACKAGE_MANIFEST = ROOT / "pkg_joomleague.xml"
CHANGELOG = ROOT / "CHANGELOG.md"
REPOSITORY = "klucon/com_joomleague"
MAINTAINER = "Ondřej Klučka"
MAINTAINER_URL = "https://klucon.cz"
JOOMLA_TARGET = "6.*"
PHP_MINIMUM = "8.3"


def version() -> str:
    value = ET.parse(PACKAGE_MANIFEST).getroot().findtext("version")

    if not value:
        raise RuntimeError("Missing package version")

    return value.strip()


def indent(element: ET.Element) -> str:
    rough = ET.tostring(element, encoding="utf-8")
    parsed = xml.dom.minidom.parseString(rough)
    return parsed.toprettyxml(indent="  ", encoding="utf-8").decode("utf-8")


def changelog_sections() -> list[tuple[str, list[str]]]:
    content = CHANGELOG.read_text(encoding="utf-8")
    matches = list(
        re.finditer(
            r"^##\s+(?P<version>\d+\.\d+\.\d+)\s+-\s+\d{4}-\d{2}-\d{2}\s*$"
            r"(?P<body>.*?)(?=^##\s+|\Z)",
            content,
            re.MULTILINE | re.DOTALL,
        )
    )

    if not matches:
        raise RuntimeError("CHANGELOG.md does not contain any dated release sections")

    sections: list[tuple[str, list[str]]] = []
    for match in matches:
        items = []

        for line in match.group("body").splitlines():
            line = line.strip()

            if line.startswith("- "):
                items.append(line[2:].strip())

        sections.append((match.group("version"), items or [f"Release {match.group('version')}."]))

    return sections


def write_update_xml(release_version: str, output: Path) -> None:
    tag = f"v{release_version}"
    release_url = f"https://github.com/{REPOSITORY}/releases/tag/{tag}"
    download_url = f"https://github.com/{REPOSITORY}/releases/download/{tag}/pkg_joomleague-{release_version}.zip"
    changelog_url = f"https://github.com/{REPOSITORY}/releases/download/{tag}/joomleague-changelog.xml"

    updates = ET.Element("updates")
    update = ET.SubElement(updates, "update")
    ET.SubElement(update, "name").text = "JoomLeague"
    ET.SubElement(update, "description").text = "JoomLeague package for Joomla 6"
    ET.SubElement(update, "element").text = "pkg_joomleague"
    ET.SubElement(update, "type").text = "package"
    ET.SubElement(update, "version").text = release_version
    ET.SubElement(update, "infourl", {"title": "JoomLeague"}).text = release_url

    downloads = ET.SubElement(update, "downloads")
    ET.SubElement(downloads, "downloadurl", {"type": "full", "format": "zip"}).text = download_url

    tags = ET.SubElement(update, "tags")
    ET.SubElement(tags, "tag").text = "stable"

    ET.SubElement(update, "maintainer").text = MAINTAINER
    ET.SubElement(update, "maintainerurl").text = MAINTAINER_URL
    ET.SubElement(update, "targetplatform", {"name": "joomla", "version": JOOMLA_TARGET})
    ET.SubElement(update, "php_minimum").text = PHP_MINIMUM
    ET.SubElement(update, "changelogurl").text = changelog_url

    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(indent(updates), encoding="utf-8")


def write_changelog_xml(release_version: str, output: Path) -> None:
    changelogs = ET.Element("changelogs")

    for section_version, items in changelog_sections():
        changelog = ET.SubElement(changelogs, "changelog")
        ET.SubElement(changelog, "element").text = "pkg_joomleague"
        ET.SubElement(changelog, "type").text = "package"
        ET.SubElement(changelog, "version").text = section_version

        for item in items:
            ET.SubElement(changelog, "change").text = item

    if not any(section_version == release_version for section_version, _ in changelog_sections()):
        raise RuntimeError(f"Missing CHANGELOG.md section for {release_version}")

    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(indent(changelogs), encoding="utf-8")


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--version", default=version())
    parser.add_argument("--update-output", default=DIST / "joomleague-update.xml", type=Path)
    parser.add_argument("--changelog-output", default=DIST / "joomleague-changelog.xml", type=Path)
    args = parser.parse_args()

    write_update_xml(args.version, args.update_output)
    write_changelog_xml(args.version, args.changelog_output)
    print(args.update_output)
    print(args.changelog_output)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
