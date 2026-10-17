<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

/** Sport classification only; this tree never resolves sporting rules. */
final class SportCategoryTree
{
	/** @param list<array{id: int, parent_id: int, code: string}> $categories */
	public function validate(array $categories): void
	{
		if (count($categories) > 10000) {
			throw new \InvalidArgumentException('category_limit');
		}
		$parents = [];
		$codes = [];
		foreach ($categories as $category) {
			$id = $category['id'] ?? null;
			$parent = $category['parent_id'] ?? null;
			$code = $category['code'] ?? null;
			if (!is_int($id) || $id < 1 || !is_int($parent) || $parent < 0 || !is_string($code)
				|| preg_match('/^[a-z][a-z0-9_-]{0,99}$/D', $code) !== 1) {
				throw new \InvalidArgumentException('category_invalid');
			}
			if (isset($parents[$id]) || isset($codes[$code])) {
				throw new \InvalidArgumentException('category_duplicate');
			}
			$parents[$id] = $parent;
			$codes[$code] = true;
		}
		$complete = [];
		foreach ($parents as $id => $parent) {
			$path = [];
			while ($id !== 0 && !isset($complete[$id])) {
				if (!array_key_exists($id, $parents)) {
					throw new \InvalidArgumentException('category_parent_missing');
				}
				if (isset($path[$id])) {
					throw new \InvalidArgumentException('category_cycle');
				}
				$path[$id] = true;
				$id = $parents[$id];
			}
			$complete += $path;
		}
	}

	/** @param list<array{id: int, parent_id: int, code: string}> $categories */
	public function assertRemovable(array $categories, int $id, int $sportAssignments): void
	{
		$this->validate($categories);
		if ($sportAssignments < 0) {
			throw new \InvalidArgumentException('category_invalid');
		}
		$found = false;
		foreach ($categories as $category) {
			$found = $found || $category['id'] === $id;
			if ($category['parent_id'] === $id) {
				throw new \InvalidArgumentException('category_has_children');
			}
		}
		if (!$found) {
			throw new \InvalidArgumentException('category_missing');
		}
		if ($sportAssignments !== 0) {
			throw new \InvalidArgumentException('category_in_use');
		}
	}
}
