#!/usr/bin/env python3
"""Build and publish the local JoomLeague release distribution."""

from __future__ import annotations

import subprocess
import sys
import urllib.request
import xml.etree.ElementTree as ET
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PACKAGE_MANIFEST = ROOT / "pkg_joomleague.xml"
PUBLIC_URLS = (
    "https://download.klucon.cz/",
    "https://download.klucon.cz/joomleague/",
    "https://download.klucon.cz/joomleague/releases/",
    "https://download.klucon.cz/joomleague/languages/",
    "https://download.klucon.cz/joomleague/dev/",
    "https://update.klucon.cz/joomleague/update.xml",
    "https://update.klucon.cz/joomleague/languages/manifest.json",
)


def version() -> str:
    value = ET.parse(PACKAGE_MANIFEST).getroot().findtext("version")

    if not value:
        raise RuntimeError("Missing package version")

    return value.strip()


def run_step(script: str) -> None:
    command = [sys.executable, str(ROOT / "build" / script)]
    print("+", " ".join(command), flush=True)
    subprocess.run(command, cwd=ROOT, check=True)


def check_url(url: str) -> None:
    request = urllib.request.Request(url, method="HEAD")

    with urllib.request.urlopen(request, timeout=15) as response:
        status = response.getcode()

    if status < 200 or status >= 400:
        raise RuntimeError(f"{url} returned HTTP {status}")

    print(f"OK {status} {url}")


def main() -> int:
    release_version = version()

    for script in (
        "validate_versions.py",
        "language_packages.py",
        "package.py",
        "release_metadata.py",
        "validate_package.py",
        "update_distribution.py",
    ):
        run_step(script)

    urls = (*PUBLIC_URLS, f"https://download.klucon.cz/joomleague/releases/{release_version}/")

    for url in urls:
        check_url(url)

    print(f"Published JoomLeague {release_version}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
