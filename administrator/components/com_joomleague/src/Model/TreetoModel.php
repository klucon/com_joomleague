<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\Database\ParameterType;

final class TreetoModel extends EntityAdminModel
{
	protected string $entityName = 'treeto';

	protected function prepareTable($table): void
	{
		$table->project_id = (int) $table->project_id;
		$table->division_id = (int) $table->division_id ?: null;
		$table->tree_i = (int) $table->tree_i;
		$table->name = trim((string) $table->name);
		$table->global_bestof = (int) $table->global_bestof;
		$table->global_matchday = (int) $table->global_matchday;
		$table->global_known = (int) $table->global_known;
		$table->global_fake = (int) $table->global_fake;
		$table->leafed = (int) $table->leafed;
		$table->mirror = (int) $table->mirror;
		$table->hide = (int) $table->hide;
		$table->published = (int) $table->published;
		$table->modified = (new Date())->toSql();
		$table->modified_by = (int) $this->getCurrentUser()->id ?: null;
	}

	public function generateNodes(int $treetoId): int
	{
		$db = $this->getDatabase();
		$tree = $db->setQuery($db->createQuery()->select('*')->from('#__joomleague_treeto')->where('id = :id')->bind(':id', $treetoId, ParameterType::INTEGER))->loadObject();

		if (!$tree || (int) $tree->tree_i < 1) {
			return 0;
		}

		$db->setQuery($db->createQuery()->delete('#__joomleague_treeto_node')->where('treeto_id = :id')->bind(':id', $treetoId, ParameterType::INTEGER))->execute();

		$depth = (int) $tree->tree_i;
		$total = (2 ** ($depth + 1)) - 1;
		$columns = ['treeto_id', 'node', 'row', 'bestof', 'title', 'published', 'is_leaf'];
		$query = $db->createQuery()->insert('#__joomleague_treeto_node')->columns(array_map([$db, 'quoteName'], $columns));

		for ($node = 1; $node <= $total; $node++) {
			$row = $this->calculateNodeRow($node, $depth);
			$isLeaf = $node >= (2 ** $depth) ? 1 : 0;
			$query->values(implode(',', [
				(int) $treetoId,
				(int) $node,
				(int) $row,
				(int) $tree->global_bestof,
				$db->quote(''),
				1,
				$isLeaf,
			]));
		}

		$db->setQuery($query)->execute();
		$db->setQuery($db->createQuery()->update('#__joomleague_treeto')->set('leafed = 1')->where('id = :id')->bind(':id', $treetoId, ParameterType::INTEGER))->execute();

		return $total;
	}

	private function calculateNodeRow(int $node, int $depth): int
	{
		$i = $depth;
		$x = $node;
		$base = 2 ** $i;
		$row = $base;

		while ($x > 1) {
			if ($x >= (2 ** $i)) {
				$row += ($x % 2 === 1 ? 1 : -1) * (int) ($base * (1 / (2 ** $i)));
				$i--;
				$x = (int) floor($x / 2);
			} else {
				$i--;
			}
		}

		return max(1, $row);
	}
}
