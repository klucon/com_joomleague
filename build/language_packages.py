#!/usr/bin/env python3
"""Build JoomLeague language ZIP packages from the local Weblate VCS checkout."""

from __future__ import annotations

import re
import sys
import zipfile
from collections import defaultdict
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
WEBLATE_VCS = Path("/mnt/disk-a/docker/apps/translate/weblate-data/vcs")
OUTPUT_DIR = ROOT / "dist" / "languages"
HELPER = ROOT / "administrator/components/com_joomleague/src/Helper/LanguageStatusHelper.php"
SOURCE_LANGUAGE = "en-GB"


def language_tags() -> list[str]:
    content = HELPER.read_text(encoding="utf-8")
    block_match = re.search(r"private const AVAILABLE_LANGUAGES = \[(.*?)\];", content, re.S)

    if not block_match:
        raise RuntimeError("Could not find AVAILABLE_LANGUAGES in LanguageStatusHelper.php")

    return re.findall(r"'([a-z]{2}(?:-[A-Z]{2})?)'\s*=>", block_match.group(1))


def target_path(path: Path, tag: str) -> str | None:
    parts = path.parts

    try:
        language_index = next(i for i, part in enumerate(parts) if part == "language" and i + 1 < len(parts) and parts[i + 1] == tag)
    except StopIteration:
        return None

    name = path.name

    if not name.endswith(".ini") or "joomleague" not in name:
        return None

    prefix_parts = parts[:language_index]

    if "administrator" in prefix_parts or "plugins" in prefix_parts:
        return f"administrator/language/{tag}/{name}"

    if "components" in prefix_parts or "modules" in prefix_parts or (language_index >= 2 and prefix_parts[-1] not in {"administrator", "components", "modules", "plugins"}):
        return f"language/{tag}/{name}"

    return None


def source_priority(path: Path) -> tuple[int, int, str]:
    text = path.as_posix()

    if "/_failed_imports_" in text or "/glossary/" in text or "/.git/" in text:
        return (99, len(text), text)

    if "/joomleague/administrator-component/" in text or "/com-joomleague/administrator-component/" in text:
        return (0, len(text), text)

    if "/joomleague/" in text or "/com-joomleague/" in text:
        return (1, len(text), text)

    return (2, len(text), text)


def collect_language_files(tag: str) -> tuple[dict[str, Path], dict[str, list[Path]]]:
    candidates: dict[str, list[Path]] = defaultdict(list)

    for path in WEBLATE_VCS.rglob("*.ini"):
        text = path.as_posix()

        if f"/language/{tag}/" not in text:
            continue

        if "/_failed_imports_" in text or "/glossary/" in text or "/.git/" in text:
            continue

        relative = path.relative_to(WEBLATE_VCS)
        target = target_path(relative, tag)

        if target is not None:
            candidates[target].append(path)

    selected: dict[str, Path] = {}
    collisions: dict[str, list[Path]] = {}

    for target, paths in candidates.items():
        paths.sort(key=source_priority)
        selected[target] = paths[0]
        selected_priority = source_priority(paths[0])[0]
        comparable_paths = [path for path in paths if source_priority(path)[0] == selected_priority]
        contents = {path.read_bytes() for path in comparable_paths}

        if len(contents) > 1:
            collisions[target] = comparable_paths

    return selected, collisions


def build_package(tag: str) -> tuple[int, int]:
    files, collisions = collect_language_files(tag)

    if not files:
        return (0, 0)

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    package = OUTPUT_DIR / f"joomleague-language-{tag}.zip"

    with zipfile.ZipFile(package, "w", compression=zipfile.ZIP_DEFLATED) as archive:
        for target, source in sorted(files.items()):
            archive.write(source, target)

    return (len(files), len(collisions))


def main() -> int:
    if not WEBLATE_VCS.is_dir():
        print(f"Weblate VCS directory not found: {WEBLATE_VCS}", file=sys.stderr)
        return 1

    total = 0

    for tag in language_tags():
        if tag == SOURCE_LANGUAGE:
            continue

        files, collisions = build_package(tag)

        if files:
            total += 1
            print(f"{tag}: {files} files, {collisions} changed-content collisions")

    print(f"Built {total} language packages in {OUTPUT_DIR}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
