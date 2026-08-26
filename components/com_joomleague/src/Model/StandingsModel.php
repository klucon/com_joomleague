<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\StandingsReader;

/**
 * Reads a published standings snapshot for the site "standings" view — the
 * first real site page this component ever had, so it can be assigned as a
 * Joomla menu item. Read-only: reuses the same StandingsReader the module
 * and the admin Standings screen already use, per the shared domain layer
 * rule (see docs/FRONTEND_MODULE_ROADMAP.md).
 */
final class StandingsModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null)
	{
		$input = Factory::getApplication()->getInput();

		// project_id/stage_id are "request" menu-item fields (part of the
		// menu item's own link query string when active), not "params" —
		// read straight off Input the same way the module reads its params.
		$this->setState('project_id', $input->getInt('project_id', 0));

		$stageId = $input->getInt('stage_id', 0);
		$this->setState('stage_id', $stageId > 0 ? $stageId : null);

		$scope = $input->getCmd('scope', '');
		$this->setState('scope', $scope !== '' ? $scope : null);
	}

	/** @return array<string,mixed> */
	public function getStandings(): array
	{
		$projectId = (int) $this->getState('project_id');

		if ($projectId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_STANDINGS_NO_PROJECT'];
		}

		Factory::getApplication()->bootComponent('com_joomleague');

		$database = Factory::getContainer()->get(DatabaseInterface::class);

		if (!$this->isPublishedContext($database, $projectId, $this->getState('stage_id'))) {
			return ['error' => 'COM_JOOMLEAGUE_STANDINGS_UNAVAILABLE'];
		}

		$reader = new StandingsReader($database);
		$stageId = $this->getState('stage_id');

		try {
			$context = $reader->describe($projectId, $stageId);

			$scope = (string) $this->getState('scope');
			if ($scope === '' || !\in_array($scope, $context['available_scopes'], true)) {
				$scope = (string) $context['default_scope'];
			}

			$current = $reader->current($projectId, $stageId, $scope);
		} catch (\Throwable $exception) {
			return ['error' => 'COM_JOOMLEAGUE_STANDINGS_UNAVAILABLE'];
		}

		if ($current['snapshot'] === null) {
			return ['error' => 'COM_JOOMLEAGUE_STANDINGS_VIEW_EMPTY', 'project' => $context['project']];
		}

		$params = Factory::getApplication()->getParams();
		$rows = $current['rows'];
		$highlightEntryId = (int) $params->get('highlight_entry_id', 0);
		$limit = (int) $params->get('limit', 0);

		// Zone boundaries are absolute row positions. A tied rank can occur
		// on several rows, so rank_number cannot identify a single boundary.
		// Resolve positions before any row-limit windowing happens.
		$this->markZoneBoundaries($rows, $params);

		// The row-limit window still centres on the highlighted entry even
		// when its visual style is "none" — centring and the visual cue are
		// independent choices (mirrors mod_joomleague_standings).
		if ($limit > 0 && \count($rows) > $limit) {
			$rows = $this->windowRows($rows, $limit, $highlightEntryId);
		}

		$standingsType = (string) ($context['standings_type'] ?? 'team_table');
		$formEnabled = $standingsType !== 'race_results'
			&& (string) ($context['profile']['contest']['type'] ?? '') === 'head_to_head'
			&& (int) $params->get('form_enabled', 0) === 1;
		$form = [];
		if ($formEnabled) {
			$formCount = max(1, (int) $params->get('form_count', 5));
			$entryIds = array_values(array_unique(array_map(static fn ($row) => (int) $row->project_entry_id, $rows)));
			$outcomeSource = (string) ($context['contract']['outcome_source'] ?? 'root_numeric');
			$includedStatuses = $context['contract']['included_result_statuses'] ?? ['final'];
			$form = $reader->recentForm($projectId, $stageId, $entryIds, $formCount, $outcomeSource, $includedStatuses);
			// recentForm() returns most-recent-first (natural for "trim to
			// the last N"); flip to chronological order so the template can
			// render oldest-to-newest, most recent result on the right.
			$form = array_map('array_reverse', $form);
		}

		$highlightStyle = (string) $params->get('highlight_style', 'row');
		if (!\in_array($highlightStyle, ['row', 'text', 'none'], true)) {
			$highlightStyle = 'row';
		}

		$highlightColorRow = $this->sanitizeColor((string) $params->get('highlight_color_row', '#ffc107'), '#ffc107');
		$highlightColorText = $this->sanitizeColor((string) $params->get('highlight_color_text', '#000000'), '#000000');

		// Three independent Yes/No radio switches, not a checkbox group: an
		// HTML checkbox group with nothing checked submits no value at all,
		// so an admin turning every decoration off would have their choice
		// silently discarded and fall back to the "bold" default on save. A
		// radio pair always has exactly one option selected and therefore
		// always submits a value, whichever way the admin sets it.
		return [
			'project' => $context['project'],
			'stage' => $context['stage'],
			'standings_type' => $standingsType,
			'columns' => $this->buildColumns($context['contract']['metrics'] ?? [], $params->get('metric_codes', []), (int) $params->get('combined_score_format', 0) === 1),
			'short_labels' => (int) $params->get('short_labels', 0) === 1,
			'short_label_tooltips' => (int) $params->get('short_label_tooltips', 1) === 1,
			'snapshot' => $current['snapshot'],
			'rows' => $rows,
			'scope' => $scope,
			'available_scopes' => $context['available_scopes'],
			'highlight_entry_id' => $highlightEntryId,
			'highlight_style' => $highlightStyle,
			'highlight_color_row' => $highlightColorRow,
			'highlight_color_text' => $highlightColorText,
			'highlight_bold' => (int) $params->get('highlight_bold', 1) === 1,
			'highlight_italic' => (int) $params->get('highlight_italic', 0) === 1,
			'highlight_underline' => (int) $params->get('highlight_underline', 0) === 1,
			'responsive_columns' => (int) $params->get('responsive_columns', 0) === 1,
			'form_enabled' => $formEnabled,
			'form' => $form,
		];
	}

	private function isPublishedContext(DatabaseInterface $database, int $projectId, mixed $stageId): bool
	{
		$query = $database->getQuery(true)
			->select('COUNT(*)')
			->from($database->quoteName('#__joomleague_project', 'project'))
			->innerJoin($database->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id')
			->innerJoin($database->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id')
			->innerJoin($database->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id')
			->where('project.id = :project')
			->where('project.published = 1')
			->where('competition.published = 1')
			->where('season.published = 1')
			->where('sport_type.published = 1')
			->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($database, 'project'))
			->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($database, 'competition'))
			->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($database, 'season'))
			->bind(':project', $projectId, \Joomla\Database\ParameterType::INTEGER);

		if ($stageId !== null) {
			$query->innerJoin($database->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.project_id = project.id')
				->where('stage.id = :stage')
				->where('stage.published = 1')
				->bind(':stage', $stageId, \Joomla\Database\ParameterType::INTEGER);
		}

		return (int) $database->setQuery($query)->loadResult() === 1;
	}

	private function sanitizeColor(string $color, string $fallback): string
	{
		return preg_match('/^#[0-9a-fA-F]{3}(?:[0-9a-fA-F]{3})?$/', $color) ? $color : $fallback;
	}

	/**
	 * Marks each row with whether it's the boundary row of a top/bottom
	 * zone (e.g. promotion/relegation), by mutating a `zone_top`/
	 * `zone_bottom` property directly onto the row objects — same approach
	 * StandingsReader already uses for `->metrics`. Row positions in the
	 * full table are absolute, so this must run before any row-limit
	 * window slices $rows down.
	 *
	 * @param list<object> $rows
	 */
	private function markZoneBoundaries(array $rows, Registry $params): void
	{
		$total = \count($rows);

		$topEnabled = (int) $params->get('zone_top_enabled', 0) === 1;
		$topCount = max(0, (int) $params->get('zone_top_count', 3));
		$topColor = $this->sanitizeColor((string) $params->get('zone_top_color', '#28a745'), '#28a745');

		$bottomEnabled = (int) $params->get('zone_bottom_enabled', 0) === 1;
		$bottomCount = max(0, (int) $params->get('zone_bottom_count', 3));
		$bottomColor = $this->sanitizeColor((string) $params->get('zone_bottom_color', '#dc3545'), '#dc3545');

		foreach ($rows as $index => $row) {
			$position = $index + 1;
			$row->zone_top = ($topEnabled && $topCount > 0 && $topCount < $total && $position === $topCount) ? $topColor : null;
			$row->zone_bottom = ($bottomEnabled && $bottomCount > 0 && $bottomCount < $total && $position === $total - $bottomCount + 1) ? $bottomColor : null;
		}
	}

	/**
	 * @param list<object> $rows
	 * @return list<object>
	 */
	private function windowRows(array $rows, int $limit, int $highlightEntryId): array
	{
		$highlightIndex = null;

		if ($highlightEntryId > 0) {
			foreach ($rows as $index => $row) {
				if ((int) $row->project_entry_id === $highlightEntryId) {
					$highlightIndex = $index;
					break;
				}
			}
		}

		if ($highlightIndex === null) {
			return \array_slice($rows, 0, $limit);
		}

		$start = $highlightIndex - \intdiv($limit, 2);
		$start = max(0, min($start, \count($rows) - $limit));

		return \array_slice($rows, $start, $limit);
	}

	/**
	 * @param array<int,array{code:string}> $metrics
	 * @param mixed $metricCodes Checkboxes field posts an array; kept
	 *     tolerant of a comma-separated string too (e.g. hand-edited params).
	 * @return array<int,array{code:string}>
	 */
	private function filterMetrics(array $metrics, mixed $metricCodes): array
	{
		$codes = \is_array($metricCodes) ? $metricCodes : explode(',', (string) $metricCodes);
		$allowed = array_filter(array_map(static fn ($code) => strtolower(trim((string) $code)), $codes));

		if ($allowed === []) {
			return array_values(array_filter(
				$metrics,
				static fn (array $metric): bool => !\in_array((string) ($metric['operation'] ?? ''), ['status_order'], true)
			));
		}

		return array_values(array_filter(
			$metrics,
			static fn (array $metric) => \in_array(strtolower((string) ($metric['code'] ?? '')), $allowed, true)
		));
	}

	/**
	 * Builds the ordered list of table columns from the (already filtered)
	 * metrics. With the combined-score toggle on, any "*_for"/"*_against"
	 * pair still present after filtering collapses into one "xx:xx" column
	 * at the position of whichever half appears first — generic across
	 * every sport profile's for/against metric family (score, legs, sets,
	 * maps, rounds...), not hardcoded to football's score_for/score_against.
	 *
	 * @param array<int,array{code:string}> $metrics
	 * @param mixed $metricCodes see filterMetrics()
	 * @return list<array{type:'single',code:string}|array{type:'combined',for:string,against:string,prefix:string}>
	 */
	private function buildColumns(array $metrics, mixed $metricCodes, bool $combine): array
	{
		$metrics = $this->filterMetrics($metrics, $metricCodes);
		$codes = array_map(static fn (array $m) => (string) $m['code'], $metrics);
		$definitions = [];
		foreach ($metrics as $metric) {
			$definitions[(string) $metric['code']] = $metric;
		}

		if (!$combine) {
			return array_map(static fn (string $code) => ['type' => 'single', 'code' => $code, 'definition' => $definitions[$code]], $codes);
		}

		$present = array_fill_keys($codes, true);

		// Only pair codes where BOTH halves survived filtering — map either
		// half to its shared prefix so the loop below can collapse whichever
		// one it meets first, regardless of which was listed first.
		$pairPrefix = [];
		foreach ($codes as $code) {
			if (!str_ends_with($code, '_for')) {
				continue;
			}
			$prefix = substr($code, 0, -4);
			$against = $prefix . '_against';
			if (isset($present[$against])) {
				$pairPrefix[$code] = $prefix;
				$pairPrefix[$against] = $prefix;
			}
		}

		$columns = [];
		$emitted = [];

		foreach ($codes as $code) {
			$prefix = $pairPrefix[$code] ?? null;

			if ($prefix !== null) {
				if (isset($emitted[$prefix])) {
					continue;
				}
				$emitted[$prefix] = true;
				$columns[] = ['type' => 'combined', 'for' => $prefix . '_for', 'against' => $prefix . '_against', 'prefix' => $prefix];
				continue;
			}

			$columns[] = ['type' => 'single', 'code' => $code, 'definition' => $definitions[$code]];
		}

		return $columns;
	}
}
