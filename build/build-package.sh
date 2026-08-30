#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="${ROOT}/build/package"
DIST_DIR="${ROOT}/dist"
"${ROOT}/tests/Architecture/verify-release.sh"
VERSION="$(sed -n 's:.*<version>\([^<]*\)</version>.*:\1:p' "${ROOT}/build/pkg_joomleague.xml" | head -n 1)"
PACKAGE="${DIST_DIR}/com_joomleague-${VERSION}.zip"
PLUGIN_PACKAGE="${DIST_DIR}/plg_quickicon_joomleague-${VERSION}.zip"
CONSOLE_PLUGIN_PACKAGE="${DIST_DIR}/plg_console_joomleague-${VERSION}.zip"
TASK_PLUGIN_PACKAGE="${DIST_DIR}/plg_task_joomleague-${VERSION}.zip"
FINDER_PLUGIN_PACKAGE="${DIST_DIR}/plg_finder_joomleague-${VERSION}.zip"
CONTENT_PLUGIN_PACKAGE="${DIST_DIR}/plg_content_joomleague-${VERSION}.zip"
STANDINGS_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_standings-${VERSION}.zip"
PROGRAM_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_program-${VERSION}.zip"
STATRANKING_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_statranking-${VERSION}.zip"
EVENTRANKING_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_eventranking-${VERSION}.zip"
SUITE_PACKAGE="${DIST_DIR}/pkg_joomleague-${VERSION}.zip"

if [[ -d "${BUILD_DIR}" ]]; then
	find "${BUILD_DIR}" -mindepth 1 -delete
fi
mkdir -p "${BUILD_DIR}/admin" "${BUILD_DIR}/site" "${BUILD_DIR}/media" "${DIST_DIR}"

cp "${ROOT}/administrator/components/com_joomleague/joomleague.xml" "${BUILD_DIR}/joomleague.xml"
cp "${ROOT}/administrator/components/com_joomleague/script.php" "${BUILD_DIR}/script.php"
cp -R "${ROOT}/administrator/components/com_joomleague/." "${BUILD_DIR}/admin/"
cp -R "${ROOT}/components/com_joomleague/." "${BUILD_DIR}/site/"
cp -R "${ROOT}/media/com_joomleague/." "${BUILD_DIR}/media/"

find "${DIST_DIR}" -maxdepth 1 -type f -name "$(basename "${PACKAGE}")" -delete
(
	cd "${BUILD_DIR}"
	zip -qr "${PACKAGE}" .
)

printf '%s\n' "${PACKAGE}"

	find "${DIST_DIR}" -maxdepth 1 -type f \( -name "$(basename "${PLUGIN_PACKAGE}")" -o -name "$(basename "${CONSOLE_PLUGIN_PACKAGE}")" -o -name "$(basename "${TASK_PLUGIN_PACKAGE}")" -o -name "$(basename "${FINDER_PLUGIN_PACKAGE}")" -o -name "$(basename "${CONTENT_PLUGIN_PACKAGE}")" -o -name "$(basename "${STANDINGS_MODULE_PACKAGE}")" -o -name "$(basename "${PROGRAM_MODULE_PACKAGE}")" -o -name "$(basename "${STATRANKING_MODULE_PACKAGE}")" -o -name "$(basename "${EVENTRANKING_MODULE_PACKAGE}")" -o -name "$(basename "${SUITE_PACKAGE}")" \) -delete
(
	cd "${ROOT}/plugins/quickicon/joomleague"
	zip -qr "${PLUGIN_PACKAGE}" .
)

(
	cd "${ROOT}/plugins/console/joomleague"
	zip -qr "${CONSOLE_PLUGIN_PACKAGE}" .
)

(
	cd "${ROOT}/plugins/task/joomleague"
	zip -qr "${TASK_PLUGIN_PACKAGE}" .
)

(
	cd "${ROOT}/plugins/finder/joomleague"
	zip -qr "${FINDER_PLUGIN_PACKAGE}" .
)

(
	cd "${ROOT}/plugins/content/joomleague"
	zip -qr "${CONTENT_PLUGIN_PACKAGE}" .
)

(
	cd "${ROOT}/modules/mod_joomleague_standings"
	zip -qr "${STANDINGS_MODULE_PACKAGE}" .
)

(
	cd "${ROOT}/modules/mod_joomleague_program"
	zip -qr "${PROGRAM_MODULE_PACKAGE}" .
)

(
	cd "${ROOT}/modules/mod_joomleague_statranking"
	zip -qr "${STATRANKING_MODULE_PACKAGE}" .
)
(
	cd "${ROOT}/modules/mod_joomleague_eventranking"
	zip -qr "${EVENTRANKING_MODULE_PACKAGE}" .
)

PACKAGE_STAGE="$(mktemp -d)"
trap 'find "${PACKAGE_STAGE}" -mindepth 1 -delete; rmdir "${PACKAGE_STAGE}"' EXIT
cp "${ROOT}/build/pkg_joomleague.xml" "${PACKAGE_STAGE}/pkg_joomleague.xml"
cp "${ROOT}/build/pkg_script.php" "${PACKAGE_STAGE}/pkg_script.php"
cp -R "${ROOT}/build/language" "${PACKAGE_STAGE}/language"
cp "${PACKAGE}" "${PLUGIN_PACKAGE}" "${CONSOLE_PLUGIN_PACKAGE}" "${TASK_PLUGIN_PACKAGE}" "${FINDER_PLUGIN_PACKAGE}" "${CONTENT_PLUGIN_PACKAGE}" "${STANDINGS_MODULE_PACKAGE}" "${PROGRAM_MODULE_PACKAGE}" "${STATRANKING_MODULE_PACKAGE}" "${EVENTRANKING_MODULE_PACKAGE}" "${PACKAGE_STAGE}/"
(
	cd "${PACKAGE_STAGE}"
	zip -qr "${SUITE_PACKAGE}" .
)

printf '%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n' "${PLUGIN_PACKAGE}" "${CONSOLE_PLUGIN_PACKAGE}" "${TASK_PLUGIN_PACKAGE}" "${FINDER_PLUGIN_PACKAGE}" "${CONTENT_PLUGIN_PACKAGE}" "${STANDINGS_MODULE_PACKAGE}" "${PROGRAM_MODULE_PACKAGE}" "${STATRANKING_MODULE_PACKAGE}" "${EVENTRANKING_MODULE_PACKAGE}" "${SUITE_PACKAGE}"
