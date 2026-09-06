<?php

/**
 * @package     Joomleague.Site
 * @subpackage  mod_joomleague_standings
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\Standings\Site\Helper;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\StandingsReader;
use Joomleague\Component\Joomleague\Site\Service\ProjectTemplateProvider;
use Joomleague\Component\Joomleague\Site\Service\RankingColumnFilter;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Reads published standings snapshots via the shared domain StandingsReader
 * service — read-only, no recalculation capability.
 *
 * Booting the component registers the \Domain namespace (see
 * administrator/components/com_joomleague/services/provider.php) regardless of
 * client, so the site module reaches the same sport-profile-aware standings
 * engine the admin uses without duplicating it and without reaching into the
 * admin's own \Administrator namespace. Metric labels are resolved from the
 * component's own site-side language file (components/com_joomleague/language),
 * not the administrator one.
 *
 * Mirrors Site\Model\StandingsModel (the "standings" site view) feature for
 * feature — same params names, same behaviour — just reading them straight
 * off the module's own Registry instead of Factory::getApplication()->getParams().
 */
class StandingsHelper
{
    /** @return array<string,mixed> */
    public function getStandings(Registry $params): array
    {
        $projectId = (int) $params->get('project_id');

        if ($projectId < 1) {
            return ['error' => 'MOD_JOOMLEAGUE_STANDINGS_NO_PROJECT'];
        }

        Factory::getApplication()->bootComponent('com_joomleague');

        // Mirrors Joomla's own ComponentDispatcher::loadLanguage() fallback
        // (JPATH_SITE, then JPATH_SITE/components/com_joomleague): a module
        // calling Language::load() directly does not go through the
        // dispatcher, so it must replicate the same two-tier lookup or
        // translations silently fail whenever the site language file has
        // not been installed to the root site language folder.
        $language = Factory::getLanguage();
        $language->load('com_joomleague', JPATH_SITE)
            || $language->load('com_joomleague', JPATH_SITE . '/components/com_joomleague');

        $database = Factory::getContainer()->get(DatabaseInterface::class);
        $reader = new StandingsReader($database);

        $stageId = (int) $params->get('stage_id', 0);
        $stageId = $stageId > 0 ? $stageId : null;

        try {
            $context = $reader->describe($projectId, $stageId);
            $scope = (string) $context['default_scope'];
            $current = $reader->current($projectId, $stageId, $scope);
        } catch (\Throwable $exception) {
            return ['error' => 'MOD_JOOMLEAGUE_STANDINGS_UNAVAILABLE'];
        }

        if ($current['snapshot'] === null) {
            return ['error' => 'MOD_JOOMLEAGUE_STANDINGS_EMPTY', 'project' => $context['project']];
        }

        $rows = $current['rows'];
        $highlightEntryId = (int) $params->get('highlight_entry_id', 0);
        $limit = (int) $params->get('limit', 0);

        // Zone boundaries are absolute ranks (rank 3 of 14 stays rank 3 of
        // 14 regardless of the row-limit window below), so they must be
        // resolved against the full row count before any windowing happens.
        $this->markZoneBoundaries($rows, $params);

        // The row-limit window still centres on the highlighted entry even
        // when its visual style is "none" — centring and the visual cue are
        // independent choices.
        if ($limit > 0 && \count($rows) > $limit) {
            $rows = $this->windowRows($rows, $limit, $highlightEntryId);
        }

        $formEnabled = (int) $params->get('form_enabled', 0) === 1;
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

        $presentationOverrides = [];
        foreach (['show_score', 'show_goal_difference', 'show_sets', 'show_points'] as $field) {
            $value = (string) $params->get('template_' . $field, '');
            if ($value === '0' || $value === '1') {
                $presentationOverrides[$field] = $value === '1';
            }
        }
        try {
            $provider = new ProjectTemplateProvider($database);
            $templateConfig = $provider->supports($projectId, 'ranking')
                ? $provider->resolve($projectId, 'ranking', $presentationOverrides)
                : [];
        } catch (\Throwable) {
            $templateConfig = [];
        }

        $highlightStyle = (string) $params->get('highlight_style', '');
        if ($highlightStyle === '') {
            $highlightStyle = (string) ($templateConfig['favorite_highlight_mode'] ?? 'row');
        }
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
        $columns = $this->buildColumns($context['contract']['metrics'] ?? [], $params->get('metric_codes', []), (int) $params->get('combined_score_format', 0) === 1);
        if ($templateConfig !== []) {
            $columns = (new RankingColumnFilter())->apply($columns, $templateConfig);
        }

        return [
            'project' => $context['project'],
            'columns' => $columns,
            'short_labels' => (int) $params->get('short_labels', 0) === 1,
            'short_label_tooltips' => (int) $params->get('short_label_tooltips', 1) === 1,
            'snapshot' => $current['snapshot'],
            'rows' => $rows,
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

    private function sanitizeColor(string $color, string $fallback): string
    {
        return preg_match('/^#[0-9a-fA-F]{3}(?:[0-9a-fA-F]{3})?$/', $color) ? $color : $fallback;
    }

    /**
     * Marks each row with whether it's the boundary row of a top/bottom
     * zone (e.g. promotion/relegation), by mutating a `zone_top`/
     * `zone_bottom` property directly onto the row objects — same approach
     * StandingsReader already uses for `->metrics`. Ranks are absolute
     * positions in the full table, so this must run before any row-limit
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

        foreach ($rows as $row) {
            $rank = (int) $row->rank_number;
            $row->zone_top = ($topEnabled && $topCount > 0 && $topCount < $total && $rank === $topCount) ? $topColor : null;
            $row->zone_bottom = ($bottomEnabled && $bottomCount > 0 && $bottomCount < $total && $rank === $total - $bottomCount + 1) ? $bottomColor : null;
        }
    }

    /**
     * Top-N by default; but if the highlighted entry falls outside that
     * window, centre the window on it instead — a "row limit" is only
     * useful in the fairly common case where the admin's own team isn't
     * near the top of the table.
     *
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
            return $metrics;
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

        if (!$combine) {
            return array_map(static fn (string $code) => ['type' => 'single', 'code' => $code], $codes);
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

            $columns[] = ['type' => 'single', 'code' => $code];
        }

        return $columns;
    }
}
