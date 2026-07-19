#!/usr/bin/env python3
"""Generate Joomla update and changelog XML files for a release."""

from __future__ import annotations

import argparse
import hashlib
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
UPDATE_BASE = "https://update.klucon.cz/joomleague"
JOOMLA_TARGET = "6.*"
PHP_MINIMUM = "8.3"
PUBLIC_ROLLUP_BASE = {
    "6.1.0-alpha-150": 61,
    "6.1.0-alpha-153": 151,
    "6.1.0-alpha-154": 153,
}


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
            r"^##\s+(?P<version>\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?)\s+-\s+\d{4}-\d{2}-\d{2}\s*$"
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


def alpha_number(release_version: str) -> int | None:
    match = re.search(r"-alpha-(\d+)$", release_version)

    return int(match.group(1)) if match else None


def public_rollup_items(release_version: str, sections: list[tuple[str, list[str]]]) -> list[str] | None:
    base = PUBLIC_ROLLUP_BASE.get(release_version)
    current = alpha_number(release_version)

    if base is None or current is None:
        return None

    items = [
        f"Public {release_version} update summary: this release replaces the previous public alpha-{base} package and includes all listed alpha changes from 6.1.0-alpha-{base + 1} through {release_version}.",
        "Target platform: Joomla 6.1 and PHP 8.3. This is still an alpha release intended for evaluation, migration testing and demo deployments.",
        "Main focus areas: multilingual SEF routing, project-aware URLs, administrator menu item stability, frontend page polish, calendar output, running-race groundwork and package metadata.",
    ]

    for section_version, section_items in sections:
        section_alpha = alpha_number(section_version)

        if section_alpha is None or section_alpha <= base or section_alpha > current:
            continue

        for item in section_items:
            items.append(f"{section_version}: {item.replace('`', '')}")

    return items


def package_zip(release_version: str) -> Path:
    path = DIST / f"pkg_joomleague-{release_version}.zip"

    if not path.is_file():
        raise RuntimeError(f"Missing package ZIP for checksum: {path.relative_to(ROOT)}")

    return path


def sha256(path: Path) -> str:
    digest = hashlib.sha256()

    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)

    return digest.hexdigest()


def write_update_xml(release_version: str, output: Path) -> None:
    tag = f"v{release_version}"
    release_url = f"https://github.com/{REPOSITORY}/releases/tag/{tag}"
    download_url = f"{UPDATE_BASE}/packages/pkg_joomleague-{release_version}.zip"
    changelog_url = f"{UPDATE_BASE}/changelog-{release_version}.xml"
    checksum = sha256(package_zip(release_version))

    updates = ET.Element("updates")
    update = ET.SubElement(updates, "update")
    ET.SubElement(update, "name").text = "JoomLeague"
    ET.SubElement(update, "description").text = "JoomLeague package for Joomla 6"
    ET.SubElement(update, "element").text = "pkg_joomleague"
    ET.SubElement(update, "type").text = "package"
    ET.SubElement(update, "client").text = "0"
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
    ET.SubElement(update, "sha256").text = checksum
    ET.SubElement(update, "changelogurl").text = changelog_url

    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(indent(updates), encoding="utf-8")


def write_changelog_xml(release_version: str, output: Path) -> None:
    changelogs = ET.Element("changelogs")
    sections = changelog_sections()
    rollup_items = public_rollup_items(release_version, sections)

    for section_version, items in sections:
        changelog = ET.SubElement(changelogs, "changelog")
        ET.SubElement(changelog, "element").text = "pkg_joomleague"
        ET.SubElement(changelog, "type").text = "package"
        ET.SubElement(changelog, "version").text = section_version
        changes = ET.SubElement(changelog, "change")

        if section_version == release_version and rollup_items is not None:
            items = rollup_items

        for item in items:
            ET.SubElement(changes, "item").text = item

    if not any(section_version == release_version for section_version, _ in sections):
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
    versioned_changelog = args.changelog_output.with_name(f"joomleague-changelog-{args.version}.xml")
    versioned_changelog.write_text(args.changelog_output.read_text(encoding="utf-8"), encoding="utf-8")
    print(args.update_output)
    print(args.changelog_output)
    print(versioned_changelog)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
