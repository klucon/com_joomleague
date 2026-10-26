#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="${ROOT}/build/package"
DIST_DIR="${JOOMLEAGUE_DIST_DIR:-${ROOT}/dist}"
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
NEXT_EVENT_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_next_event-${VERSION}.zip"
NAVIGATION_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_navigation-${VERSION}.zip"
PARTICIPANT_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_participant-${VERSION}.zip"
CLUB_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_club-${VERSION}.zip"
PERSONNEL_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_personnel-${VERSION}.zip"
VENUE_PROGRAM_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_venue_program-${VERSION}.zip"
COMPETITIONS_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_competitions-${VERSION}.zip"
STATRANKING_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_statranking-${VERSION}.zip"
EVENTRANKING_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_eventranking-${VERSION}.zip"
CALENDAR_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_calendar-${VERSION}.zip"
PROGRAMME_TICKER_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_programme_ticker-${VERSION}.zip"
BIRTHDAYS_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_birthdays-${VERSION}.zip"
SPOTLIGHT_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_spotlight-${VERSION}.zip"
LATEST_RESULTS_MODULE_PACKAGE="${DIST_DIR}/mod_joomleague_latest_results-${VERSION}.zip"
SUITE_PACKAGE="${DIST_DIR}/pkg_joomleague-${VERSION}.zip"

# Keep separately distributed translation sources without leaking them into release ZIPs.
declare -A separate_languages=()
for language_root in "${ROOT}/administrator/components/com_joomleague/language" "${ROOT}/components/com_joomleague/language" "${ROOT}/build/language" "${ROOT}"/modules/*/language "${ROOT}"/plugins/*/*/language; do
	[[ -d "${language_root}" ]] || continue
	for language_directory in "${language_root}"/*; do
		[[ -d "${language_directory}" ]] || continue
		tag="$(basename "${language_directory}")"
		[[ "${tag}" =~ ^[a-z]{2,3}-[A-Z]{2}$ ]] || { printf 'Invalid language directory: %s\n' "${tag}" >&2; exit 1; }
		[[ "${tag}" == en-GB ]] || separate_languages["${tag}"]=1
	done
done
ZIP_LANGUAGE_FILTER=()
if (( ${#separate_languages[@]} > 0 )); then
	ZIP_LANGUAGE_FILTER=(-x)
	for tag in "${!separate_languages[@]}"; do
		ZIP_LANGUAGE_FILTER+=("language/${tag}/*" "admin/language/${tag}/*" "site/language/${tag}/*")
	done
fi

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
	zip -qr "${PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

printf '%s\n' "${PACKAGE}"

find "${DIST_DIR}" -maxdepth 1 -type f \( -name "$(basename "${BIRTHDAYS_MODULE_PACKAGE}")" -o -name "$(basename "${SPOTLIGHT_MODULE_PACKAGE}")" -o -name "$(basename "${LATEST_RESULTS_MODULE_PACKAGE}")" \) -delete

	find "${DIST_DIR}" -maxdepth 1 -type f \( -name "$(basename "${PLUGIN_PACKAGE}")" -o -name "$(basename "${CONSOLE_PLUGIN_PACKAGE}")" -o -name "$(basename "${TASK_PLUGIN_PACKAGE}")" -o -name "$(basename "${FINDER_PLUGIN_PACKAGE}")" -o -name "$(basename "${CONTENT_PLUGIN_PACKAGE}")" -o -name "$(basename "${STANDINGS_MODULE_PACKAGE}")" -o -name "$(basename "${PROGRAM_MODULE_PACKAGE}")" -o -name "$(basename "${NEXT_EVENT_MODULE_PACKAGE}")" -o -name "$(basename "${NAVIGATION_MODULE_PACKAGE}")" -o -name "$(basename "${PARTICIPANT_MODULE_PACKAGE}")" -o -name "$(basename "${CLUB_MODULE_PACKAGE}")" -o -name "$(basename "${PERSONNEL_MODULE_PACKAGE}")" -o -name "$(basename "${VENUE_PROGRAM_MODULE_PACKAGE}")" -o -name "$(basename "${COMPETITIONS_MODULE_PACKAGE}")" -o -name "$(basename "${STATRANKING_MODULE_PACKAGE}")" -o -name "$(basename "${EVENTRANKING_MODULE_PACKAGE}")" -o -name "$(basename "${CALENDAR_MODULE_PACKAGE}")" -o -name "$(basename "${PROGRAMME_TICKER_MODULE_PACKAGE}")" -o -name "$(basename "${SUITE_PACKAGE}")" \) -delete
(
	cd "${ROOT}/plugins/quickicon/joomleague"
	zip -qr "${PLUGIN_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

(
	cd "${ROOT}/plugins/console/joomleague"
	zip -qr "${CONSOLE_PLUGIN_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

(
	cd "${ROOT}/plugins/task/joomleague"
	zip -qr "${TASK_PLUGIN_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

(
	cd "${ROOT}/plugins/finder/joomleague"
	zip -qr "${FINDER_PLUGIN_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

(
	cd "${ROOT}/plugins/content/joomleague"
	zip -qr "${CONTENT_PLUGIN_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

(
	cd "${ROOT}/modules/mod_joomleague_standings"
	zip -qr "${STANDINGS_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

(
	cd "${ROOT}/modules/mod_joomleague_program"
	zip -qr "${PROGRAM_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

(
	cd "${ROOT}/modules/mod_joomleague_next_event"
	zip -qr "${NEXT_EVENT_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

(
	cd "${ROOT}/modules/mod_joomleague_navigation"
	zip -qr "${NAVIGATION_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

(
	cd "${ROOT}/modules/mod_joomleague_participant"
	zip -qr "${PARTICIPANT_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

(
	cd "${ROOT}/modules/mod_joomleague_club"
	zip -qr "${CLUB_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

(
	cd "${ROOT}/modules/mod_joomleague_personnel"
	zip -qr "${PERSONNEL_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

(
	cd "${ROOT}/modules/mod_joomleague_venue_program"
	zip -qr "${VENUE_PROGRAM_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

(
	cd "${ROOT}/modules/mod_joomleague_competitions"
	zip -qr "${COMPETITIONS_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

(
	cd "${ROOT}/modules/mod_joomleague_statranking"
	zip -qr "${STATRANKING_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)
(
	cd "${ROOT}/modules/mod_joomleague_eventranking"
	zip -qr "${EVENTRANKING_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)
(
	cd "${ROOT}/modules/mod_joomleague_calendar"
	zip -qr "${CALENDAR_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)
(
	cd "${ROOT}/modules/mod_joomleague_programme_ticker"
	zip -qr "${PROGRAMME_TICKER_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)
(
	cd "${ROOT}/modules/mod_joomleague_birthdays"
	zip -qr "${BIRTHDAYS_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)
(
	cd "${ROOT}/modules/mod_joomleague_spotlight"
	zip -qr "${SPOTLIGHT_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)
(
	cd "${ROOT}/modules/mod_joomleague_latest_results"
	zip -qr "${LATEST_RESULTS_MODULE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

PACKAGE_STAGE="$(mktemp -d)"
trap 'find "${PACKAGE_STAGE}" -mindepth 1 -delete; rmdir "${PACKAGE_STAGE}"' EXIT
cp "${ROOT}/build/pkg_joomleague.xml" "${PACKAGE_STAGE}/pkg_joomleague.xml"
cp "${ROOT}/build/pkg_script.php" "${PACKAGE_STAGE}/pkg_script.php"
cp -R "${ROOT}/build/language" "${PACKAGE_STAGE}/language"
COUNTRIES_ROOT="${KLUCONCOUNTRIES_ROOT:-${ROOT}/vendor/klucon-countries}"
CONNECTOR_ROOT="${KLUCONCOUNTRIES_CONNECTOR_ROOT:-${ROOT}/vendor/klucon-countries-connector}"
if [[ -z "${KLUCONCOUNTRIES_ROOT:-}" && ! -f "${COUNTRIES_ROOT}/tools/build.php" ]]; then COUNTRIES_ROOT="${ROOT}/../com_kluconcountries"; fi
if [[ -z "${KLUCONCOUNTRIES_CONNECTOR_ROOT:-}" && ! -f "${CONNECTOR_ROOT}/joomleague.xml" ]]; then CONNECTOR_ROOT="${ROOT}/../plg_kluconcountries_joomleague"; fi
mkdir -p "${PACKAGE_STAGE}/dependencies"
php "${COUNTRIES_ROOT}/tools/build.php" "${PACKAGE_STAGE}/dependencies/com_kluconcountries.zip"
(
	cd "${CONNECTOR_ROOT}"
	zip -qr "${PACKAGE_STAGE}/plg_kluconcountries_joomleague-${VERSION}.zip" joomleague.xml services src language/en-GB
)
cp "${PACKAGE}" "${PLUGIN_PACKAGE}" "${CONSOLE_PLUGIN_PACKAGE}" "${TASK_PLUGIN_PACKAGE}" "${FINDER_PLUGIN_PACKAGE}" "${CONTENT_PLUGIN_PACKAGE}" "${STANDINGS_MODULE_PACKAGE}" "${PROGRAM_MODULE_PACKAGE}" "${NEXT_EVENT_MODULE_PACKAGE}" "${NAVIGATION_MODULE_PACKAGE}" "${PARTICIPANT_MODULE_PACKAGE}" "${CLUB_MODULE_PACKAGE}" "${PERSONNEL_MODULE_PACKAGE}" "${VENUE_PROGRAM_MODULE_PACKAGE}" "${COMPETITIONS_MODULE_PACKAGE}" "${STATRANKING_MODULE_PACKAGE}" "${EVENTRANKING_MODULE_PACKAGE}" "${CALENDAR_MODULE_PACKAGE}" "${PROGRAMME_TICKER_MODULE_PACKAGE}" "${BIRTHDAYS_MODULE_PACKAGE}" "${SPOTLIGHT_MODULE_PACKAGE}" "${LATEST_RESULTS_MODULE_PACKAGE}" "${PACKAGE_STAGE}/"
(
	cd "${PACKAGE_STAGE}"
	zip -qr "${SUITE_PACKAGE}" . "${ZIP_LANGUAGE_FILTER[@]}"
)

node "${ROOT}/tests/Architecture/verify-package-languages.cjs" "${SUITE_PACKAGE}"

printf '%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n%s\n' "${PLUGIN_PACKAGE}" "${CONSOLE_PLUGIN_PACKAGE}" "${TASK_PLUGIN_PACKAGE}" "${FINDER_PLUGIN_PACKAGE}" "${CONTENT_PLUGIN_PACKAGE}" "${STANDINGS_MODULE_PACKAGE}" "${PROGRAM_MODULE_PACKAGE}" "${NEXT_EVENT_MODULE_PACKAGE}" "${NAVIGATION_MODULE_PACKAGE}" "${PARTICIPANT_MODULE_PACKAGE}" "${CLUB_MODULE_PACKAGE}" "${PERSONNEL_MODULE_PACKAGE}" "${VENUE_PROGRAM_MODULE_PACKAGE}" "${COMPETITIONS_MODULE_PACKAGE}" "${STATRANKING_MODULE_PACKAGE}" "${EVENTRANKING_MODULE_PACKAGE}" "${CALENDAR_MODULE_PACKAGE}" "${PROGRAMME_TICKER_MODULE_PACKAGE}" "${BIRTHDAYS_MODULE_PACKAGE}" "${SPOTLIGHT_MODULE_PACKAGE}" "${LATEST_RESULTS_MODULE_PACKAGE}" "${SUITE_PACKAGE}"
