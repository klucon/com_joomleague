<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class SportCategoryService
{
	public function __construct(private readonly DatabaseInterface $db) {}

	/** @return list<object> */
	public function categories(): array
	{
		$query = $this->db->getQuery(true)->select('c.*')
			->select('(SELECT COUNT(*) FROM ' . $this->db->quoteName('#__joomleague_sport_profile_category') . ' m WHERE m.category_id = c.id) AS profile_count')
			->from($this->db->quoteName('#__joomleague_sport_category', 'c'))->order(['c.ordering ASC', 'c.name ASC', 'c.id ASC']);
		$items = $this->db->setQuery($query)->loadObjectList();
		$byId = array_column($items, null, 'id');
		foreach ($items as $item) {
			$names = [$item->name];
			$parent = (int) $item->parent_id;
			$seen = [(int) $item->id => true];
			while ($parent > 0 && isset($byId[$parent]) && !isset($seen[$parent])) {
				$seen[$parent] = true;
				array_unshift($names, $byId[$parent]->name);
				$parent = (int) $byId[$parent]->parent_id;
			}
			$item->path = implode(' / ', $names);
		}
		$children = [];
		foreach ($items as $item) $children[(int) $item->parent_id][] = $item;
		$ordered = [];
		$stack = array_reverse($children[0] ?? []);
		while ($stack !== []) {
			$item = array_pop($stack);
			$ordered[] = $item;
			foreach (array_reverse($children[(int) $item->id] ?? []) as $child) $stack[] = $child;
		}
		return $ordered;
	}

	/** @param array<string,mixed> $data */
	public function save(array $data, int $userId, bool $canChangeState = true): int
	{
		$id = $this->integer($data['id'] ?? 0);
		$parent = $this->integer($data['parent_id'] ?? 0);
		$name = $data['name'] ?? '';
		$code = $data['code'] ?? '';
		$description = $data['description'] ?? '';
		$published = $this->integer($data['published'] ?? 1);
		$ordering = $this->integer($data['ordering'] ?? 0);
		if (!is_string($name) || !is_string($code) || !is_string($description) || trim($name) === ''
			|| mb_strlen(trim($name)) > 255 || strlen($description) > 10000 || $ordering > 2147483647 || !in_array($published, [0, 1], true)) {
			$this->fail('INVALID');
		}
		$name = trim($name);
		$code = trim($code);
		return $this->write(function () use ($id, $parent, $name, $code, $description, $published, $ordering, $userId, $canChangeState): int {
			$rows = $this->tree();
			$existing = array_column($rows, null, 'id');
			if ($id > 0 && !isset($existing[$id])) $this->fail('MISSING');
			if (!$canChangeState) {
				$published = $id > 0 ? (int) $this->db->setQuery('SELECT published FROM ' . $this->db->quoteName('#__joomleague_sport_category') . ' WHERE id = ' . $id)->loadResult() : 0;
			}
			$nodeId = $id ?: (max([0, ...array_column($rows, 'id')]) + 1);
			$rows = array_values(array_filter($rows, static fn (array $row): bool => $row['id'] !== $id));
			$rows[] = ['id' => $nodeId, 'parent_id' => $parent, 'code' => $code];
			try { (new SportCategoryTree())->validate($rows); }
			catch (\InvalidArgumentException $error) { $this->fail(strtoupper(substr($error->getMessage(), 9))); }
			$record = (object) [
				'id' => $id, 'parent_id' => $parent ?: null, 'name' => $name, 'code' => $code,
				'description' => $description, 'published' => $published, 'ordering' => $ordering,
			];
			if ($id === 0) {
				unset($record->id);
				$record->uuid = UuidFactory::v4();
				$record->created = gmdate('Y-m-d H:i:s');
				$record->created_by = $userId;
				$this->db->insertObject('#__joomleague_sport_category', $record, 'id');
			} else {
				$record->modified = gmdate('Y-m-d H:i:s');
				$record->modified_by = $userId;
				$this->db->updateObject('#__joomleague_sport_category', $record, 'id', true);
			}
			return (int) $record->id;
		});
	}

	public function delete(int $id): void
	{
		$this->write(function () use ($id): void {
			$boundId = $id;
			$query = $this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__joomleague_sport_profile_category'))
				->where('category_id = :id')->bind(':id', $boundId, ParameterType::INTEGER);
			$count = (int) $this->db->setQuery($query)->loadResult();
			try { (new SportCategoryTree())->assertRemovable($this->tree(), $id, $count); }
			catch (\InvalidArgumentException $error) { $this->fail(strtoupper(substr($error->getMessage(), 9))); }
			$query = $this->db->getQuery(true)->delete($this->db->quoteName('#__joomleague_sport_category'))
				->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
			$this->db->setQuery($query)->execute();
		});
	}

	/** Replace classification only, never profile contracts or project bindings. */
	public function assign(array $profileIds, array $categoryIds): void
	{
		$profiles = $this->ids($profileIds);
		$categories = $this->ids($categoryIds);
		if ($profiles === [] || count($profiles) * count($categories) > 10000) $this->fail('INVALID');
		$this->write(function () use ($profiles, $categories): void {
			foreach (['sport_profile' => $profiles, 'sport_category' => $categories] as $table => $ids) {
				if ($ids === []) continue;
				$query = $this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__joomleague_' . $table))
					->where('id IN (' . implode(',', $ids) . ')');
				if ((int) $this->db->setQuery($query)->loadResult() !== count($ids)) $this->fail('MISSING');
			}
			$query = $this->db->getQuery(true)->delete($this->db->quoteName('#__joomleague_sport_profile_category'))
				->where('profile_id IN (' . implode(',', $profiles) . ')');
			$this->db->setQuery($query)->execute();
			foreach ($profiles as $profileId) foreach ($categories as $categoryId) {
				$assignment = (object) ['profile_id' => $profileId, 'category_id' => $categoryId];
				$this->db->insertObject('#__joomleague_sport_profile_category', $assignment);
			}
		});
	}

	/** @return list<array{id:int,parent_id:int,code:string}> */
	private function tree(): array
	{
		$query = $this->db->getQuery(true)->select(['id', 'parent_id', 'code'])->from($this->db->quoteName('#__joomleague_sport_category'));
		return array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'parent_id' => (int) $row['parent_id'], 'code' => $row['code']], $this->db->setQuery($query)->loadAssocList());
	}

	private function write(callable $operation): mixed
	{
		$this->db->transactionStart();
		try {
			// A permanent singleton serializes hierarchy changes and assignment/deletion races.
			$this->db->setQuery('SELECT id FROM ' . $this->db->quoteName('#__joomleague_sport_category_lock') . ' WHERE id = 1 FOR UPDATE');
			if ((int) $this->db->loadResult() !== 1) throw new \RuntimeException('Missing sport classification write lock.');
			$result = $operation();
			$this->db->transactionCommit();
			return $result;
		} catch (\Throwable $error) {
			$this->db->transactionRollback();
			throw $error;
		}
	}

	private function integer(mixed $value): int
	{
		if ((!is_int($value) && !is_string($value)) || filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0) $this->fail('INVALID');
		return (int) $value;
	}

	/** @return list<int> */
	private function ids(array $values): array
	{
		if (count($values) > 1000) $this->fail('INVALID');
		$ids = [];
		foreach ($values as $value) {
			$id = $this->integer($value);
			if ($id === 0) $this->fail('INVALID');
			$ids[$id] = $id;
		}
		return array_values($ids);
	}

	private function fail(string $reason): never
	{
		throw new \InvalidArgumentException(Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_ERROR_' . $reason));
	}
}
