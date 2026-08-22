#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="${ROOT}/build/package"
DIST_DIR="${ROOT}/dist"
PACKAGE="${DIST_DIR}/com_joomleague-6.2.0-dev.zip"
PLUGIN_PACKAGE="${DIST_DIR}/plg_quickicon_joomleague-6.2.0-dev.zip"
CONSOLE_PLUGIN_PACKAGE="${DIST_DIR}/plg_console_joomleague-6.2.0-dev.zip"
TASK_PLUGIN_PACKAGE="${DIST_DIR}/plg_task_joomleague-6.2.0-dev.zip"
SUITE_PACKAGE="${DIST_DIR}/pkg_joomleague-6.2.0-dev.zip"

if [[ -d "${BUILD_DIR}" ]]; then
	find "${BUILD_DIR}" -mindepth 1 -delete
fi
mkdir -p "${BUILD_DIR}/admin" "${BUILD_DIR}/site" "${BUILD_DIR}/media" "${DIST_DIR}"

cp "${ROOT}/administrator/components/com_joomleague/joomleague.xml" "${BUILD_DIR}/joomleague.xml"
cp "${ROOT}/administrator/components/com_joomleague/script.php" "${BUILD_DIR}/script.php"
cp -a "${ROOT}/administrator/components/com_joomleague/." "${BUILD_DIR}/admin/"
cp -a "${ROOT}/components/com_joomleague/." "${BUILD_DIR}/site/"
cp -a "${ROOT}/media/com_joomleague/." "${BUILD_DIR}/media/"

find "${DIST_DIR}" -maxdepth 1 -type f -name "$(basename "${PACKAGE}")" -delete
(
	cd "${BUILD_DIR}"
	zip -qr "${PACKAGE}" .
)

printf '%s\n' "${PACKAGE}"

find "${DIST_DIR}" -maxdepth 1 -type f \( -name "$(basename "${PLUGIN_PACKAGE}")" -o -name "$(basename "${CONSOLE_PLUGIN_PACKAGE}")" -o -name "$(basename "${TASK_PLUGIN_PACKAGE}")" -o -name "$(basename "${SUITE_PACKAGE}")" \) -delete
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

PACKAGE_STAGE="$(mktemp -d)"
trap 'find "${PACKAGE_STAGE}" -mindepth 1 -delete; rmdir "${PACKAGE_STAGE}"' EXIT
cp "${ROOT}/build/pkg_joomleague.xml" "${PACKAGE_STAGE}/pkg_joomleague.xml"
cp "${ROOT}/build/pkg_script.php" "${PACKAGE_STAGE}/pkg_script.php"
cp "${PACKAGE}" "${PLUGIN_PACKAGE}" "${CONSOLE_PLUGIN_PACKAGE}" "${TASK_PLUGIN_PACKAGE}" "${PACKAGE_STAGE}/"
(
	cd "${PACKAGE_STAGE}"
	zip -qr "${SUITE_PACKAGE}" .
)

printf '%s\n%s\n%s\n%s\n' "${PLUGIN_PACKAGE}" "${CONSOLE_PLUGIN_PACKAGE}" "${TASK_PLUGIN_PACKAGE}" "${SUITE_PACKAGE}"
