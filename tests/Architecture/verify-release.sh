#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PACKAGE_MANIFEST="${ROOT}/build/pkg_joomleague.xml"
VERSION="$(xmllint --xpath 'string(/extension/version)' "${PACKAGE_MANIFEST}")"

if [[ -z "${VERSION}" ]] || ! php -r 'exit(version_compare($argv[1], "6.2.0-dev", ">=") ? 0 : 1);' "${VERSION}"; then
	printf '%s\n' 'Package version is missing or invalid.' >&2
	exit 1
fi

MANIFESTS=(
	"${ROOT}/administrator/components/com_joomleague/joomleague.xml"
	"${ROOT}/modules/mod_joomleague_standings/mod_joomleague_standings.xml"
	"${ROOT}/modules/mod_joomleague_program/mod_joomleague_program.xml"
	"${ROOT}/modules/mod_joomleague_next_event/mod_joomleague_next_event.xml"
	"${ROOT}/modules/mod_joomleague_navigation/mod_joomleague_navigation.xml"
	"${ROOT}/modules/mod_joomleague_participant/mod_joomleague_participant.xml"
	"${ROOT}/modules/mod_joomleague_club/mod_joomleague_club.xml"
	"${ROOT}/modules/mod_joomleague_personnel/mod_joomleague_personnel.xml"
	"${ROOT}/modules/mod_joomleague_venue_program/mod_joomleague_venue_program.xml"
	"${ROOT}/modules/mod_joomleague_competitions/mod_joomleague_competitions.xml"
	"${ROOT}/modules/mod_joomleague_statranking/mod_joomleague_statranking.xml"
	"${ROOT}/modules/mod_joomleague_eventranking/mod_joomleague_eventranking.xml"
	"${ROOT}/plugins/console/joomleague/joomleague.xml"
	"${ROOT}/plugins/quickicon/joomleague/joomleague.xml"
	"${ROOT}/plugins/task/joomleague/joomleague.xml"
	"${ROOT}/plugins/finder/joomleague/joomleague.xml"
	"${ROOT}/plugins/content/joomleague/joomleague.xml"
)

for manifest in "${MANIFESTS[@]}"; do
	xmllint --noout "${manifest}"
	test "$(xmllint --xpath 'string(/extension/version)' "${manifest}")" = "${VERSION}"
done

test "$(xmllint --xpath 'string(/extension/updateservers/server)' "${PACKAGE_MANIFEST}")" = 'https://downloads.joomleague.eu/update.xml'
test "$(xmllint --xpath 'string(/extension/updateservers/server/@type)' "${PACKAGE_MANIFEST}")" = 'extension'

FILE_COUNT="$(xmllint --xpath 'count(/extension/files/file)' "${PACKAGE_MANIFEST}")"
for ((index = 1; index <= FILE_COUNT; index++)); do
	filename="$(xmllint --xpath "string(/extension/files/file[${index}])" "${PACKAGE_MANIFEST}")"
	[[ "${filename}" == *"-${VERSION}.zip" ]]
done

grep -Fq "'component_version' => '${VERSION}'" \
	"${ROOT}/administrator/components/com_joomleague/src/Service/SystemDiagnosticsService.php"

printf 'Release contract OK: %s\n' "${VERSION}"
