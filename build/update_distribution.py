#!/usr/bin/env python3
"""Publish static JoomLeague update files into a webroot."""

from __future__ import annotations

import hashlib
import html
import json
import shutil
import xml.etree.ElementTree as ET
from datetime import datetime, timezone
from pathlib import Path
from zoneinfo import ZoneInfo


ROOT = Path(__file__).resolve().parents[1]
DIST = ROOT / "dist"
PACKAGE_MANIFEST = ROOT / "pkg_joomleague.xml"
DEFAULT_WEBROOT = Path("/mnt/disk-a/docker/webserver/update.klucon.cz/public")
BASE_URL = "https://update.klucon.cz/joomleague"
DOWNLOAD_BASE_URL = "https://downloads.klucon.cz/joomleague"
DISPLAY_TIMEZONE = ZoneInfo("Europe/Prague")


def version() -> str:
    value = ET.parse(PACKAGE_MANIFEST).getroot().findtext("version")

    if not value:
        raise RuntimeError("Missing package version")

    return value.strip()


def sha256(path: Path) -> str:
    digest = hashlib.sha256()

    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)

    return digest.hexdigest()


def copy_file(source: Path, target: Path) -> None:
    if not source.is_file():
        raise RuntimeError(f"Missing source file: {source.relative_to(ROOT)}")

    target.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(source, target)


def file_info(path: Path, url: str) -> dict[str, object]:
    return {
        "name": path.name,
        "size": path.stat().st_size,
        "sha256": sha256(path),
        "url": url,
    }


def language_manifest(language_dir: Path) -> dict[str, object]:
    generated = datetime.now(timezone.utc).replace(microsecond=0).isoformat()
    languages: dict[str, dict[str, object]] = {}

    for package in sorted(language_dir.glob("joomleague-language-*.zip")):
        tag = package.stem.removeprefix("joomleague-language-")
        languages[tag] = {
            "tag": tag,
            "version": version(),
            "updated": generated,
            "size": package.stat().st_size,
            "sha256": sha256(package),
            "url": f"{BASE_URL}/languages/{package.name}",
        }

    return {
        "schema": 1,
        "name": "JoomLeague language packages",
        "version": version(),
        "generated": generated,
        "languages": languages,
    }


def release_changelog_items(changelog_xml: Path, release_version: str) -> list[str]:
    if not changelog_xml.is_file():
        return []

    root = ET.parse(changelog_xml).getroot()

    for changelog in root.findall("changelog"):
        if changelog.findtext("version") != release_version:
            continue

        return [
            item.text.strip()
            for item in changelog.findall("./change/item")
            if item.text and item.text.strip()
        ]

    return []


def bytes_label(size: int) -> str:
    value = float(size)

    for unit in ("B", "KB", "MB", "GB"):
        if value < 1024 or unit == "GB":
            return f"{value:.1f} {unit}" if unit != "B" else f"{int(value)} {unit}"

        value /= 1024

    return f"{size} B"


def display_datetime(value: object) -> str:
    if not value:
        return ""

    try:
        moment = datetime.fromisoformat(str(value).replace("Z", "+00:00"))
    except ValueError:
        return str(value)

    if moment.tzinfo is None:
        moment = moment.replace(tzinfo=timezone.utc)

    return moment.astimezone(DISPLAY_TIMEZONE).strftime("%Y-%m-%d %H:%M %Z")


def render_page(title: str, body: str) -> str:
    return f"""<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{html.escape(title)}</title>
  <style>
    :root {{
      color-scheme: light dark;
      --bg: #f6f7f9;
      --fg: #16181d;
      --muted: #606775;
      --panel: #ffffff;
      --border: #d8dde6;
      --accent: #0b64d8;
      --accent-fg: #ffffff;
      --code: #eef2f8;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }}
    @media (prefers-color-scheme: dark) {{
      :root {{
        --bg: #111419;
        --fg: #f0f3f7;
        --muted: #aab2c0;
        --panel: #191e26;
        --border: #303846;
        --accent: #6aa8ff;
        --accent-fg: #08111f;
        --code: #242b36;
      }}
    }}
    * {{ box-sizing: border-box; }}
    body {{
      margin: 0;
      background: var(--bg);
      color: var(--fg);
      line-height: 1.5;
    }}
    header, main {{
      width: min(1120px, calc(100vw - 32px));
      margin: 0 auto;
    }}
    header {{
      padding: 40px 0 20px;
    }}
    main {{
      padding: 0 0 48px;
    }}
    h1 {{
      margin: 0 0 8px;
      font-size: clamp(2rem, 5vw, 3.5rem);
      line-height: 1.05;
      letter-spacing: 0;
    }}
    h2 {{
      margin: 0 0 16px;
      font-size: 1.35rem;
    }}
    h3 {{
      margin: 24px 0 8px;
      font-size: 1rem;
    }}
    p {{
      margin: 0 0 14px;
    }}
    a {{
      color: var(--accent);
      text-decoration-thickness: 1px;
      text-underline-offset: 3px;
    }}
    code {{
      padding: 2px 5px;
      border-radius: 4px;
      background: var(--code);
      font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
      font-size: .92em;
    }}
    .muted {{
      color: var(--muted);
    }}
    .grid {{
      display: grid;
      gap: 16px;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    }}
    .panel {{
      border: 1px solid var(--border);
      border-radius: 8px;
      background: var(--panel);
      padding: 20px;
    }}
    .actions {{
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 16px;
    }}
    .button {{
      display: inline-flex;
      align-items: center;
      min-height: 40px;
      padding: 8px 14px;
      border-radius: 6px;
      background: var(--accent);
      color: var(--accent-fg);
      font-weight: 650;
      text-decoration: none;
    }}
    .button.secondary {{
      background: transparent;
      color: var(--accent);
      border: 1px solid var(--border);
    }}
    table {{
      width: 100%;
      border-collapse: collapse;
      font-size: .95rem;
    }}
    th, td {{
      padding: 10px 12px;
      border-bottom: 1px solid var(--border);
      text-align: left;
      vertical-align: top;
    }}
    th {{
      color: var(--muted);
      font-weight: 650;
    }}
    .table-wrap {{
      overflow-x: auto;
    }}
    .checksums {{
      overflow-x: auto;
      white-space: pre;
      font-size: .85rem;
      background: var(--code);
      padding: 14px;
      border-radius: 6px;
    }}
    ul.clean {{
      padding-left: 1.25rem;
      margin-top: 0;
    }}
  </style>
</head>
<body>
  <header>
    <p class="muted">Klucon Downloads</p>
    <h1>{html.escape(title)}</h1>
  </header>
  <main>
{body}
  </main>
</body>
</html>
"""


def package_link(release_version: str) -> str:
    return f"packages/pkg_joomleague-{release_version}.zip"


def release_package_link(release_version: str) -> str:
    return f"releases/{release_version}/pkg_joomleague-{release_version}.zip"


def release_page(release: dict[str, object], changelog_items: list[str], languages: dict[str, dict[str, object]]) -> str:
    release_version = str(release["version"])
    package = release["package"]
    assert isinstance(package, dict)
    language_rows = "\n".join(
        "<tr>"
        f"<td>{html.escape(tag)}</td>"
        f"<td>{bytes_label(int(info['size']))}</td>"
        f"<td><code>{html.escape(str(info['sha256'])[:16])}</code></td>"
        f"<td><a href=\"languages/{html.escape(Path(str(info['name'])).name)}\">Download</a></td>"
        "</tr>"
        for tag, info in sorted(languages.items())
    )
    changelog = "\n".join(f"<li>{html.escape(item)}</li>" for item in changelog_items[:80])

    if len(changelog_items) > 80:
        changelog += f"<li>{len(changelog_items) - 80} additional changes are listed in the XML changelog.</li>"

    checksums = html.escape(str(release["checksums_text"]))

    body = f"""
    <section class="panel">
      <h2>Release {html.escape(release_version)}</h2>
      <p class="muted">Published {html.escape(display_datetime(release["generated"]))}</p>
      <p>Main package size: {bytes_label(int(package["size"]))}</p>
      <p>SHA-256: <code>{html.escape(str(package["sha256"]))}</code></p>
      <div class="actions">
        <a class="button" href="{html.escape(Path(str(package["name"])).name)}">Download package</a>
        <a class="button secondary" href="changelog.xml">Changelog XML</a>
        <a class="button secondary" href="release.json">Release JSON</a>
        <a class="button secondary" href="checksums.txt">Checksums</a>
        <a class="button secondary" href="../../releases/">All releases</a>
      </div>
    </section>

    <section class="panel">
      <h2>Changelog</h2>
      <ul class="clean">{changelog or "<li>No changelog entries found.</li>"}</ul>
    </section>

    <section class="panel">
      <h2>Language Packages</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Language</th><th>Size</th><th>SHA-256</th><th>File</th></tr></thead>
          <tbody>{language_rows}</tbody>
        </table>
      </div>
    </section>

    <section class="panel">
      <h2>Checksums</h2>
      <pre class="checksums">{checksums}</pre>
    </section>
"""
    return render_page(f"JoomLeague {release_version}", body)


def releases_index(releases: list[dict[str, object]]) -> str:
    rows = "\n".join(
        "<tr>"
        f"<td><a href=\"{html.escape(str(release['version']))}/\">{html.escape(str(release['version']))}</a></td>"
        f"<td>{html.escape(display_datetime(release['generated']))}</td>"
        f"<td>{bytes_label(int(release['package']['size']))}</td>"
        f"<td><code>{html.escape(str(release['package']['sha256'])[:16])}</code></td>"
        "</tr>"
        for release in releases
    )
    body = f"""
    <section class="panel">
      <h2>Release History</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Version</th><th>Generated</th><th>Package size</th><th>SHA-256</th></tr></thead>
          <tbody>{rows}</tbody>
        </table>
      </div>
    </section>
"""
    return render_page("JoomLeague Release History", body)


def languages_index(manifest: dict[str, object]) -> str:
    languages = manifest.get("languages", {})

    if not isinstance(languages, dict):
        languages = {}

    rows = "\n".join(
        "<tr>"
        f"<td>{html.escape(str(tag))}</td>"
        f"<td>{html.escape(str(info.get('version', '')))}</td>"
        f"<td>{html.escape(display_datetime(info.get('updated', '')))}</td>"
        f"<td>{bytes_label(int(info.get('size', 0)))}</td>"
        f"<td><code>{html.escape(str(info.get('sha256', ''))[:16])}</code></td>"
        f"<td><a href=\"{html.escape(str(info.get('url', '')))}\">Download</a></td>"
        "</tr>"
        for tag, info in sorted(languages.items())
        if isinstance(info, dict)
    )
    body = f"""
    <section class="panel">
      <h2>Language Packages</h2>
      <p class="muted">Published packages are generated from the JoomLeague translation repository and can be installed from the JoomLeague administrator language screen.</p>
      <div class="actions">
        <a class="button secondary" href="manifest.json">Language manifest JSON</a>
        <a class="button secondary" href="../">JoomLeague downloads</a>
      </div>
    </section>

    <section class="panel">
      <h2>Available Languages</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Language</th><th>Version</th><th>Updated</th><th>Size</th><th>SHA-256</th><th>File</th></tr></thead>
          <tbody>{rows}</tbody>
        </table>
      </div>
    </section>
"""
    return render_page("JoomLeague Language Packages", body)


def overview_page(current: dict[str, object], releases: list[dict[str, object]], language_count: int) -> str:
    release_version = str(current["version"])
    package = current["package"]
    assert isinstance(package, dict)
    latest_rows = "\n".join(
        "<tr>"
        f"<td><a href=\"releases/{html.escape(str(release['version']))}/\">{html.escape(str(release['version']))}</a></td>"
        f"<td>{html.escape(display_datetime(release['generated']))}</td>"
        f"<td>{bytes_label(int(release['package']['size']))}</td>"
        "</tr>"
        for release in releases[:8]
    )
    body = f"""
    <section class="grid">
      <div class="panel">
        <h2>Latest Release</h2>
        <p><strong>{html.escape(release_version)}</strong></p>
        <p class="muted">JoomLeague package for Joomla 6.</p>
        <p>Package size: {bytes_label(int(package["size"]))}</p>
        <p>SHA-256: <code>{html.escape(str(package["sha256"])[:24])}</code></p>
        <div class="actions">
          <a class="button" href="{html.escape(package_link(release_version))}">Download package</a>
          <a class="button secondary" href="releases/{html.escape(release_version)}/">Release details</a>
        </div>
      </div>
      <div class="panel">
        <h2>Update Endpoints</h2>
        <p><a href="update.xml"><code>update.xml</code></a></p>
        <p><a href="changelog.xml"><code>changelog.xml</code></a></p>
        <p><a href="languages/manifest.json"><code>languages/manifest.json</code></a></p>
        <p class="muted">{language_count} language packages are currently published.</p>
        <div class="actions">
          <a class="button secondary" href="languages/">View languages</a>
        </div>
      </div>
    </section>

    <section class="panel">
      <h2>Release History</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Version</th><th>Generated</th><th>Package size</th></tr></thead>
          <tbody>{latest_rows}</tbody>
        </table>
      </div>
      <div class="actions">
        <a class="button secondary" href="releases/">View all releases</a>
      </div>
    </section>
"""
    return render_page("JoomLeague Downloads", body)


def root_page() -> str:
    body = """
    <section class="panel">
      <h2>Available Products</h2>
      <p><a href="/joomleague/">JoomLeague downloads and update metadata</a></p>
    </section>
"""
    return render_page("Downloads", body)


def write_release(target: Path, release_version: str, package: Path, changelog_versioned: Path, language_dir: Path) -> dict[str, object]:
    generated = datetime.now(timezone.utc).replace(microsecond=0).isoformat()
    release_dir = target / "releases" / release_version
    release_language_dir = release_dir / "languages"

    copy_file(package, release_dir / package.name)
    copy_file(changelog_versioned, release_dir / "changelog.xml")
    copy_file(DIST / "joomleague-update.xml", release_dir / "update.xml")

    language_files = sorted(language_dir.glob("joomleague-language-*.zip"))
    languages: dict[str, dict[str, object]] = {}

    for language_package in language_files:
        tag = language_package.stem.removeprefix("joomleague-language-")
        target_package = release_language_dir / language_package.name
        copy_file(language_package, target_package)
        languages[tag] = file_info(
            target_package,
            f"{DOWNLOAD_BASE_URL}/releases/{release_version}/languages/{language_package.name}",
        )

    package_target = release_dir / package.name
    package_info = file_info(package_target, f"{DOWNLOAD_BASE_URL}/releases/{release_version}/{package.name}")
    changelog_info = file_info(release_dir / "changelog.xml", f"{DOWNLOAD_BASE_URL}/releases/{release_version}/changelog.xml")
    update_info = file_info(release_dir / "update.xml", f"{DOWNLOAD_BASE_URL}/releases/{release_version}/update.xml")
    checksums = [
        f"{package_info['sha256']}  {package.name}",
        f"{changelog_info['sha256']}  changelog.xml",
        f"{update_info['sha256']}  update.xml",
    ]
    checksums.extend(
        f"{info['sha256']}  languages/{info['name']}"
        for _, info in sorted(languages.items())
    )
    checksums_text = "\n".join(checksums) + "\n"
    (release_dir / "checksums.txt").write_text(checksums_text, encoding="utf-8")

    release = {
        "schema": 1,
        "name": "JoomLeague",
        "version": release_version,
        "generated": generated,
        "package": package_info,
        "changelog": changelog_info,
        "update": update_info,
        "languages": languages,
        "checksums": f"{DOWNLOAD_BASE_URL}/releases/{release_version}/checksums.txt",
        "checksums_text": checksums_text,
    }
    (release_dir / "release.json").write_text(json.dumps(release, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    changelog_items = release_changelog_items(changelog_versioned, release_version)
    (release_dir / "index.html").write_text(release_page(release, changelog_items, languages), encoding="utf-8")

    return release


def read_release_manifest(path: Path) -> dict[str, object] | None:
    manifest = path / "release.json"

    if not manifest.is_file():
        return None

    try:
        data = json.loads(manifest.read_text(encoding="utf-8"))
    except json.JSONDecodeError:
        return None

    if not isinstance(data, dict) or "version" not in data or "package" not in data:
        return None

    return data


def published_releases(target: Path, current: dict[str, object]) -> list[dict[str, object]]:
    releases: dict[str, dict[str, object]] = {str(current["version"]): current}
    release_root = target / "releases"

    if release_root.is_dir():
        for path in release_root.iterdir():
            if not path.is_dir():
                continue

            manifest = read_release_manifest(path)

            if manifest is not None:
                releases[str(manifest["version"])] = manifest

    return sorted(releases.values(), key=lambda item: str(item["version"]), reverse=True)


def main() -> int:
    release_version = version()
    target = DEFAULT_WEBROOT / "joomleague"
    package = DIST / f"pkg_joomleague-{release_version}.zip"
    changelog_versioned = DIST / f"joomleague-changelog-{release_version}.xml"
    language_dir = DIST / "languages"

    copy_file(DIST / "joomleague-update.xml", target / "update.xml")
    copy_file(DIST / "joomleague-changelog.xml", target / "changelog.xml")
    copy_file(changelog_versioned, target / f"changelog-{release_version}.xml")
    copy_file(package, target / "packages" / package.name)

    if not language_dir.is_dir():
        raise RuntimeError(f"Missing language package directory: {language_dir.relative_to(ROOT)}")

    for language_package in sorted(language_dir.glob("joomleague-language-*.zip")):
        copy_file(language_package, target / "languages" / language_package.name)

    manifest = language_manifest(language_dir)
    manifest_file = target / "languages" / "manifest.json"
    manifest_file.parent.mkdir(parents=True, exist_ok=True)
    manifest_file.write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    (target / "languages" / "index.html").write_text(languages_index(manifest), encoding="utf-8")
    current_release = write_release(target, release_version, package, changelog_versioned, language_dir)
    releases = published_releases(target, current_release)
    (target / "index.html").write_text(overview_page(current_release, releases, len(manifest["languages"])), encoding="utf-8")
    (target / "releases" / "index.html").write_text(releases_index(releases), encoding="utf-8")
    (DEFAULT_WEBROOT / "index.html").write_text(root_page(), encoding="utf-8")

    print(target)
    print(target / "update.xml")
    print(target / "languages" / "manifest.json")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
