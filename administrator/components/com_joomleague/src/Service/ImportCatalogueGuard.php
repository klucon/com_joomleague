<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Service;
defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Distinguish compatible catalogue repeats from primary/natural-key collisions. */
final class ImportCatalogueGuard
{
	private const TABLES = ['#__joomleague_sport_type', '#__joomleague_sport_position', '#__joomleague_event_type', '#__joomleague_statistic'];

	public function __construct(private readonly DatabaseInterface $db) {}

	/** @param array<string,mixed> $row */
	public function existing(string $table, array $row): bool
	{
		if (!in_array($table, self::TABLES, true)) throw new \LogicException('Unsupported catalogue table.');
		$conditions = [];
		foreach (['id', 'uuid'] as $key) {
			if (isset($row[$key])) {
				if (!is_scalar($row[$key])) throw new \InvalidArgumentException('Invalid catalogue identity.');
				$conditions[] = $this->db->quoteName($key) . '=' . $this->db->quote((string) $row[$key]);
			}
		}
		if (isset($row['code']) && is_string($row['code'])) {
			$natural = $this->db->quoteName('code') . '=' . $this->db->quote($row['code']);
			if ($table !== '#__joomleague_sport_type') {
				$natural .= ' AND sport_type_id=' . $this->id($row['sport_type_id'] ?? null);
			}
			$conditions[] = '(' . $natural . ')';
		}
		if ($conditions === []) return false;
		$query = $this->db->getQuery(true)->select('*')->from($this->db->quoteName($table))->where('(' . implode(' OR ', $conditions) . ')');
		$existing = $this->db->setQuery((string) $query . ' FOR UPDATE')->loadAssocList();
		if ($existing === []) return false;
		if (count($existing) !== 1) $this->conflict($table, $row);
		$current = $existing[0];
		// A natural-key repeat without an incoming numeric ID can preserve a local
		// catalogue row. Explicit IDs must not be remapped by a duplicate-key skip.
		foreach (['id', 'code'] as $key) if (isset($row[$key]) && (string) $row[$key] !== (string) $current[$key]) $this->conflict($table, $row);
		if (isset($row['id'], $row['uuid']) && (string) $row['uuid'] !== (string) $current['uuid']) $this->conflict($table, $row);
		$ownership = $table === '#__joomleague_sport_type' ? ['profile_version_id'] : ['sport_type_id', 'person_type', 'role_domain'];
		foreach ($ownership as $key) if (array_key_exists($key, $row) && $this->value($row[$key]) !== $this->value($current[$key] ?? null)) $this->conflict($table, $row);
		return true;
	}

	public static function protects(string $table): bool
	{
		return in_array($table, self::TABLES, true);
	}

	private function value(mixed $value): ?string
	{
		if ($value === null) return null;
		if (!is_scalar($value)) throw new \InvalidArgumentException('Invalid catalogue value.');
		return (string) $value;
	}

	private function id(mixed $value): string
	{
		if ((!is_int($value) && !is_string($value)) || preg_match('/^[1-9][0-9]{0,18}$/D', (string) $value) !== 1) throw new \InvalidArgumentException('Invalid catalogue owner.');
		return (string) $value;
	}

	private function conflict(string $table, array $row): never
	{
		// Diagnostics contain schema identifiers only, never imported names/content.
		$suffix = substr($table, strlen('#__joomleague_'));
		$id = isset($row['id']) && ctype_digit((string) $row['id']) ? (string) $row['id'] : 'auto';
		if ($id === 'auto' && isset($row['uuid']) && is_string($row['uuid']) && preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/iD', $row['uuid'])) $id = $row['uuid'];
		throw new ImportCatalogueConflict($suffix, $id);
	}
}
