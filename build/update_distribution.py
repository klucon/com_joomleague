#!/usr/bin/env python3
"""Publish static JoomLeague update files into a webroot."""

from __future__ import annotations

import hashlib
import html
import json
import re
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
DOWNLOAD_BASE_URL = "https://download.klucon.cz/joomleague"
DISPLAY_TIMEZONE = ZoneInfo("Europe/Prague")
DEV_CHANGELOGS = {
    "6.1.0-alpha-154-dev": [
        "Development build based on 6.1.0-alpha-153.",
        "Added installer schema compatibility for club and stadium latitude/longitude columns used by administrator club and stadium lists.",
        "Synced the migration tool target schema with the current component install schema so migrated databases include the same columns as fresh installations.",
        "Modernized mod_joomleague_birthday output for Joomla 6 and Bootstrap 5.",
        "Added birthday/death anniversary mode selection to mod_joomleague_birthday.",
        "Added a separate death anniversary message template to mod_joomleague_birthday.",
        "Removed the module-level timezone option and now uses the project timezone with Joomla fallback.",
        "Removed legacy module parameters Itemid, heading style and alternating table row classes from mod_joomleague_birthday.",
        "Added English, Czech and German language constants for the new birthday module options.",
        "Modernized mod_joomleague_matches output for Joomla 6 and Bootstrap 5.",
        "Added compact responsive match cards with stable score layout, optional project, round, venue, spectators, referee and match report information.",
        "Restored core mod_joomleague_matches filtering for played/upcoming windows, selected teams, date ordering and result-only/upcoming-only modes.",
        "Removed the module-level timezone option, duplicate advanced fieldset and legacy table CSS class parameters from mod_joomleague_matches.",
        "Added internal team identifiers to the shared match query so module team filters can match the selected teams correctly.",
        "Modernized mod_joomleague_ticker output for Joomla 6 and Bootstrap 5.",
        "Reworked ticker rendering into compact responsive match cards that no longer stretch the demo layout.",
        "Improved ticker contrast in Joomla template banner and topbar positions.",
        "Restored ticker filtering for match status, round, selected team, days back, result limit and date ordering.",
        "Scoped ticker team and round selectors to the selected project in the module configuration.",
        "Ignored stale ticker team filters when the stored team no longer belongs to the selected project.",
        "Ignored stale ticker round filters when the stored round no longer belongs to the selected project.",
        "Removed the module-level timezone option and duplicate advanced fieldset from mod_joomleague_ticker.",
        "Replaced raw ticker XML option labels with language constants.",
        "Scoped project-dependent selectors in module administration forms for teams, rounds, divisions, statistics, event types and matches.",
        "Added stale parameter guards for project-dependent module filters so old stored values no longer hide valid demo data.",
        "Improved random player and logo module fallbacks when no valid team is selected for the current project.",
        "Translated statistic and position names rendered from JoomLeague language constants in frontend modules.",
        "Fixed mod_joomleague_logo to render the selected team name format and optional project name instead of showing only the logo.",
    ],
}


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
  <script defer src="https://klucon.cz/media/klucon-globalbar/globalbar.js?v=20260718-2114" data-active="download" data-variant="download"></script>
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


def dev_package_version(package: Path) -> str:
    match = re.fullmatch(r"pkg_joomleague-(?P<version>.+)\.zip", package.name)

    return match.group("version") if match else package.stem


def dev_packages(dev_dir: Path) -> list[Path]:
    package_dir = dev_dir / "packages"
    package_dir.mkdir(parents=True, exist_ok=True)

    return sorted(package_dir.glob("*.zip"), key=lambda path: path.stat().st_mtime, reverse=True)


def dev_changelog_items(version_name: str) -> list[str]:
    return DEV_CHANGELOGS.get(
        version_name,
        [
            f"Development build {version_name}.",
            "This build is intended for testing only and is not part of the public update channel.",
        ],
    )


def dev_changelog_xml(version_name: str) -> str:
    items = "\n".join(f"      <item>{html.escape(item)}</item>" for item in dev_changelog_items(version_name))

    return f"""<?xml version="1.0" encoding="utf-8"?>
<changelogs>
  <changelog>
    <element>pkg_joomleague</element>
    <type>package</type>
    <version>{html.escape(version_name)}</version>
    <change>
{items}
    </change>
  </changelog>
</changelogs>
"""


def dev_update_xml(version_name: str, package: Path) -> str:
    package_url = f"{DOWNLOAD_BASE_URL}/dev/packages/{package.name}"
    changelog_url = f"{DOWNLOAD_BASE_URL}/dev/changelog-{version_name}.xml"

    return f"""<?xml version="1.0" encoding="utf-8"?>
<updates>
  <update>
    <name>JoomLeague Development Build</name>
    <description>JoomLeague development package for Joomla 6 testing</description>
    <element>pkg_joomleague</element>
    <type>package</type>
    <client>0</client>
    <version>{html.escape(version_name)}</version>
    <infourl title="JoomLeague Development Builds">{DOWNLOAD_BASE_URL}/dev/</infourl>
    <downloads>
      <downloadurl type="full" format="zip">{html.escape(package_url)}</downloadurl>
    </downloads>
    <tags>
      <tag>dev</tag>
    </tags>
    <maintainer>Ondřej Klučka</maintainer>
    <maintainerurl>https://klucon.cz</maintainerurl>
    <targetplatform name="joomla" version="6.*"/>
    <php_minimum>8.3</php_minimum>
    <sha256>{sha256(package)}</sha256>
    <changelogurl>{html.escape(changelog_url)}</changelogurl>
  </update>
</updates>
"""


def write_dev_metadata(dev_dir: Path) -> None:
    packages = dev_packages(dev_dir)

    if not packages:
        return

    latest = packages[0]
    latest_version = dev_package_version(latest)

    for package in packages:
        version_name = dev_package_version(package)
        (dev_dir / f"changelog-{version_name}.xml").write_text(dev_changelog_xml(version_name), encoding="utf-8")

    (dev_dir / "changelog.xml").write_text(dev_changelog_xml(latest_version), encoding="utf-8")
    (dev_dir / "update.xml").write_text(dev_update_xml(latest_version, latest), encoding="utf-8")


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
        f"<td data-download-version=\"{html.escape(str(release['version']))}\">-</td>"
        f"<td><code>{html.escape(str(release['package']['sha256'])[:16])}</code></td>"
        "</tr>"
        for release in releases
    )
    body = f"""
    <section class="panel">
      <h2>Release History</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Version</th><th>Generated</th><th>Package size</th><th>Downloads</th><th>SHA-256</th></tr></thead>
          <tbody>{rows}</tbody>
        </table>
      </div>
    </section>
    {download_stats_script("../download-stats.json")}
"""
    return render_page("JoomLeague Release History", body)


def dev_index(dev_dir: Path) -> str:
    packages = dev_packages(dev_dir)

    if packages:
        rows = "\n".join(
            (lambda version_name: (
            "<tr>"
            f"<td><strong>{html.escape(version_name)}</strong></td>"
            f"<td><a href=\"packages/{html.escape(package.name)}\">{html.escape(package.name)}</a></td>"
            f"<td>{html.escape(display_datetime(datetime.fromtimestamp(package.stat().st_mtime, timezone.utc).isoformat()))}</td>"
            f"<td>{bytes_label(package.stat().st_size)}</td>"
            f"<td data-download-version=\"{html.escape(version_name)}\">-</td>"
            f"<td><code>{html.escape(sha256(package)[:16])}</code></td>"
            f"<td><a href=\"changelog-{html.escape(version_name)}.xml\">Changelog</a></td>"
            "</tr>"
            ))(dev_package_version(package))
            for package in packages
        )
    else:
        rows = '<tr><td colspan="7" class="muted">No development builds are currently published.</td></tr>'

    body = f"""
    <section class="panel">
      <h2>Development Builds</h2>
      <p class="muted">Development builds are intended for testing only. They are not connected to the public Joomla update channel.</p>
      <p>Public releases use the <code>6.1.0-alpha-153</code> format. Development builds use the <code>6.1.0-alpha-154-dev</code> format.</p>
      <p class="muted">Upload development ZIP files to <code>joomleague/dev/packages/</code> with names such as <code>pkg_joomleague-6.1.0-alpha-154-dev.zip</code>.</p>
      <div class="actions">
        <a class="button secondary" href="../">JoomLeague downloads</a>
        <a class="button secondary" href="../releases/">Public releases</a>
      </div>
    </section>

    <section class="panel">
      <h2>Available Development Packages</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Version</th><th>File</th><th>Uploaded</th><th>Size</th><th>Downloads</th><th>SHA-256</th><th>Changes</th></tr></thead>
          <tbody>{rows}</tbody>
        </table>
      </div>
    </section>
    {download_stats_script("../download-stats.json")}
"""
    return render_page("JoomLeague Development Builds", body)


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
        f"<td data-download-version=\"{html.escape(str(release['version']))}\">-</td>"
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
      <div class="panel">
        <h2>Development Builds</h2>
        <p class="muted">Testing packages are published separately from the public update channel.</p>
        <div class="actions">
          <a class="button secondary" href="dev/">View development builds</a>
        </div>
      </div>
    </section>

    <section class="panel">
      <h2>Release History</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Version</th><th>Generated</th><th>Package size</th><th>Downloads</th></tr></thead>
          <tbody>{latest_rows}</tbody>
        </table>
      </div>
      <div class="actions">
        <a class="button secondary" href="releases/">View all releases</a>
      </div>
    </section>
    {download_stats_script("download-stats.json")}
"""
    return render_page("JoomLeague Downloads", body)


def download_stats_script(stats_path: str) -> str:
    return f"""
    <script>
      fetch("{html.escape(stats_path)}", {{ cache: "no-store" }})
        .then((response) => response.ok ? response.json() : null)
        .then((stats) => {{
          if (!stats) {{
            return;
          }}

          const packages = [
            ...(Array.isArray(stats.release_packages) ? stats.release_packages : []),
            ...(Array.isArray(stats.dev_packages) ? stats.dev_packages : []),
          ];
          const downloads = new Map(packages.map((item) => [String(item.version), item.downloads]));

          document.querySelectorAll("[data-download-version]").forEach((cell) => {{
            const value = downloads.get(cell.dataset.downloadVersion);
            cell.textContent = Number.isFinite(value) ? value.toLocaleString("en-US") : "0";
          }});
        }})
        .catch(() => {{}});
    </script>
"""


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
    write_dev_metadata(target / "dev")
    (target / "index.html").write_text(overview_page(current_release, releases, len(manifest["languages"])), encoding="utf-8")
    (target / "releases" / "index.html").write_text(releases_index(releases), encoding="utf-8")
    (target / "dev" / "index.html").write_text(dev_index(target / "dev"), encoding="utf-8")
    (DEFAULT_WEBROOT / "index.html").write_text(root_page(), encoding="utf-8")

    print(target)
    print(target / "update.xml")
    print(target / "languages" / "manifest.json")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
