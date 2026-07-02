#!/usr/bin/env bash
set -euo pipefail

repo="git@github.com:klucon/com_joomleague.wiki.git"
workdir="${TMPDIR:-/tmp}/com_joomleague.wiki"
source_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

rm -rf "$workdir"
git clone "$repo" "$workdir"

find "$workdir" -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf {} +
cp "$source_dir"/*.md "$workdir"/

git -C "$workdir" add .

if git -C "$workdir" diff --cached --quiet; then
	echo "Wiki is already up to date."
	exit 0
fi

git -C "$workdir" commit -m "Update wiki documentation"
git -C "$workdir" push

