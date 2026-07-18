<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

final class ProjectModel extends EntityAdminModel
{
	protected string $entityName = 'project';

	public function delete(&$pks): bool
	{
		$ids = array_map('intval', (array) $pks);
		$assets = [];

		if ($ids) {
			$db = $this->getDatabase();
			$assets = $db->setQuery('SELECT m.asset_id FROM #__joomleague_match m JOIN #__joomleague_round r ON r.id=m.round_id WHERE r.project_id IN (' . implode(',', $ids) . ') AND m.asset_id IS NOT NULL')->loadColumn();
		}

		if (!parent::delete($pks)) {
			return false;
		}

		if ($assets) {
			$this->getDatabase()->setQuery('DELETE FROM #__assets WHERE id IN (' . implode(',', array_map('intval', $assets)) . ')')->execute();
		}

		return true;
	}

	protected function prepareTable($table): void
	{
		foreach (['name', 'timezone', 'project_type', 'start_time', 'current_round', 'points_after_regular_time', 'points_after_add_time', 'points_after_penalty', 'fav_team_highlight_type', 'fav_team_color', 'fav_team_text_color', 'fav_team_text_bold', 'template', 'extended', 'picture', 'extension'] as $field) {
			$table->$field = trim((string) $table->$field);
		}

		$table->fav_team = $this->normalizeFavoriteTeams($table->fav_team ?? '');

		foreach (['league_id', 'season_id', 'sports_type_id'] as $field) {
			$table->$field = (int) $table->$field;
		}

		foreach (['master_template', 'sub_template_id'] as $field) {
			$table->$field = (int) $table->$field ?: null;
		}

		foreach (['teams_as_referees', 'current_round_auto', 'auto_time', 'game_regular_time', 'game_parts', 'halftime', 'use_legs', 'allow_add_time', 'add_time', 'enable_sb', 'sb_catid', 'published', 'ordering', 'is_utc_converted'] as $field) {
			$table->$field = (int) $table->$field;
		}

		$table->start_date = trim((string) $table->start_date) ?: null;
		$table->extended = $table->extended ?: null;
		$table->picture = $table->picture ?: null;
		$table->extension = $table->extension ?: null;

		parent::prepareTable($table);
	}

	private function normalizeFavoriteTeams(mixed $value): string
	{
		if (\is_array($value)) {
			$parts = $value;
		} else {
			$parts = explode(',', (string) $value);
		}

		$ids = array_values(array_unique(array_filter(array_map('intval', $parts))));

		return implode(',', $ids);
	}
}
