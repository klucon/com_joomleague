<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class OrganizationHistoryRepository
{
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	/** @return array{name_history:list<array<string,mixed>>,media_history:list<array<string,mixed>>} */
	public function load(string $entityType, int $entityId): array
	{
		[$ownerColumn] = $this->entity($entityType, $entityId);
		return [
			'name_history' => $this->loadTable('#__joomleague_organization_name_history', $ownerColumn, $entityId),
			'media_history' => $this->loadTable('#__joomleague_organization_media_history', $ownerColumn, $entityId),
		];
	}

	/** @param list<array<string,mixed>> $nameRows @param list<array<string,mixed>> $mediaRows */
	public function save(string $entityType, int $entityId, array $nameRows, array $mediaRows, int $actorId): void
	{
		[$ownerColumn, $otherOwnerColumn] = $this->entity($entityType, $entityId);
		if ($actorId < 0) throw new \InvalidArgumentException('Actor identifier is invalid.');
		foreach ([true, false] as $removalPass) {
			foreach ($nameRows as $ordering => $row) {
				if (is_array($row) && $this->removeRequested($row) === $removalPass) $this->saveName($row, $ownerColumn, $otherOwnerColumn, $entityId, $ordering + 1, $actorId);
			}
			foreach ($mediaRows as $ordering => $row) {
				if (is_array($row) && $this->removeRequested($row) === $removalPass) $this->saveMedia($row, $ownerColumn, $otherOwnerColumn, $entityId, $ordering + 1, $actorId);
			}
		}
	}

	/** @return list<array<string,mixed>> */
	private function loadTable(string $table, string $ownerColumn, int $entityId): array
	{
		$query = $this->database->getQuery(true)->select('*')->from($this->database->quoteName($table))
			->where($this->database->quoteName($ownerColumn) . ' = :entityId')->bind(':entityId', $entityId, ParameterType::INTEGER)
			->order('CASE WHEN ' . $this->database->quoteName('valid_from') . ' IS NULL THEN 1 ELSE 0 END ASC')
			->order($this->database->quoteName('valid_from') . ' DESC')->order($this->database->quoteName('ordering') . ' ASC')->order($this->database->quoteName('id') . ' ASC');
		return $this->database->setQuery($query)->loadAssocList();
	}

	/** @param array<string,mixed> $row */
	private function saveName(array $row, string $ownerColumn, string $otherOwnerColumn, int $entityId, int $ordering, int $actorId): void
	{
		if ($this->removeRequested($row)) {
			$this->remove('#__joomleague_organization_name_history', $row, $ownerColumn, $entityId);

			return;
		}

		$name = trim((string) ($row['name'] ?? ''));
		if ($name === '') return;
		$this->persist('#__joomleague_organization_name_history', $row, $ownerColumn, $otherOwnerColumn, $entityId, $ordering, $actorId, [
			'name' => mb_substr($name, 0, 255), 'short_name' => mb_substr(trim((string) ($row['short_name'] ?? '')), 0, 100),
			'valid_from' => $this->date($row['valid_from'] ?? null), 'valid_to' => $this->date($row['valid_to'] ?? null),
			'notes' => $this->text($row['notes'] ?? null),
		]);
	}

	/** @param array<string,mixed> $row */
	private function saveMedia(array $row, string $ownerColumn, string $otherOwnerColumn, int $entityId, int $ordering, int $actorId): void
	{
		if ($this->removeRequested($row)) {
			$this->remove('#__joomleague_organization_media_history', $row, $ownerColumn, $entityId);

			return;
		}

		$path = trim((string) ($row['media_path'] ?? ''));
		if ($path === '') return;
		$this->persist('#__joomleague_organization_media_history', $row, $ownerColumn, $otherOwnerColumn, $entityId, $ordering, $actorId, [
			'media_type' => 'logo', 'media_path' => mb_substr($path, 0, 255),
			'alt_text' => mb_substr(trim((string) ($row['alt_text'] ?? '')), 0, 255),
			'valid_from' => $this->date($row['valid_from'] ?? null), 'valid_to' => $this->date($row['valid_to'] ?? null),
			'notes' => $this->text($row['notes'] ?? null),
		]);
	}

	/** @param array<string,mixed> $submitted @param array<string,mixed> $fields */
	private function persist(string $table, array $submitted, string $ownerColumn, string $otherOwnerColumn, int $entityId, int $ordering, int $actorId, array $fields): void
	{
		$from = $fields['valid_from'] ?? null; $to = $fields['valid_to'] ?? null;
		if (is_string($from) && is_string($to) && $to < $from) throw new \InvalidArgumentException('History validity end cannot be earlier than its start.');
		$id = filter_var($submitted['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
		$now = gmdate('Y-m-d H:i:s');
		if ($id > 0) {
			$boundId = $id;
			$boundEntityId = $entityId;
			$query = $this->database->getQuery(true)->select('COUNT(*)')->from($this->database->quoteName($table))
				->where($this->database->quoteName('id') . ' = :id')->where($this->database->quoteName($ownerColumn) . ' = :entityId')
				->bind(':id', $boundId, ParameterType::INTEGER)->bind(':entityId', $boundEntityId, ParameterType::INTEGER);
			if ((int) $this->database->setQuery($query)->loadResult() !== 1) throw new \InvalidArgumentException('History row does not belong to this organization.');
			$this->assertNoOverlap($table, $ownerColumn, $entityId, $id, $from, $to);
			$record = (object) array_merge(['id' => $id, 'ordering' => $ordering, 'modified' => $now, 'modified_by' => $actorId], $fields);
			$this->database->updateObject($table, $record, 'id');
			return;
		}
		$this->assertNoOverlap($table, $ownerColumn, $entityId, 0, $from, $to);
		$record = (object) array_merge(['uuid' => UuidFactory::v4(), $ownerColumn => $entityId, $otherOwnerColumn => null, 'ordering' => $ordering, 'created' => $now, 'created_by' => $actorId], $fields);
		$this->database->insertObject($table, $record);
	}

	private function assertNoOverlap(string $table, string $ownerColumn, int $entityId, int $excludedId, ?string $validFrom, ?string $validTo): void
	{
		$query = $this->database->getQuery(true)
			->select('COUNT(*)')
			->from($this->database->quoteName($table))
			->where($this->database->quoteName($ownerColumn) . ' = :entityId')
			->bind(':entityId', $entityId, ParameterType::INTEGER);

		if ($excludedId > 0) {
			$query->where($this->database->quoteName('id') . ' <> :excludedId')
				->bind(':excludedId', $excludedId, ParameterType::INTEGER);
		}

		if ($validTo !== null) {
			$query->where('(' . $this->database->quoteName('valid_from') . ' IS NULL OR ' . $this->database->quoteName('valid_from') . ' <= :validTo)')
				->bind(':validTo', $validTo);
		}

		if ($validFrom !== null) {
			$query->where('(' . $this->database->quoteName('valid_to') . ' IS NULL OR ' . $this->database->quoteName('valid_to') . ' >= :validFrom)')
				->bind(':validFrom', $validFrom);
		}

		if ((int) $this->database->setQuery($query)->loadResult() > 0) {
			throw new \InvalidArgumentException('History validity periods cannot overlap.');
		}
	}

	/** @param array<string,mixed> $row */
	private function remove(string $table, array $row, string $ownerColumn, int $entityId): void
	{
		$id = filter_var($row['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;

		if ($id === 0) {
			return;
		}

		$query = $this->database->getQuery(true)
			->delete($this->database->quoteName($table))
			->where($this->database->quoteName('id') . ' = :id')
			->where($this->database->quoteName($ownerColumn) . ' = :entityId')
			->bind(':id', $id, ParameterType::INTEGER)
			->bind(':entityId', $entityId, ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();

		if ($this->database->getAffectedRows() !== 1) {
			throw new \InvalidArgumentException('History row does not belong to this organization.');
		}
	}

	/** @param array<string,mixed> $row */
	private function removeRequested(array $row): bool
	{
		return filter_var($row['remove_record'] ?? 0, FILTER_VALIDATE_INT) === 1;
	}

	/** @return array{0:string,1:string} */
	private function entity(string $entityType, int $entityId): array
	{
		if ($entityId < 1) throw new \InvalidArgumentException('Organization identifier is invalid.');
		return match ($entityType) { 'club' => ['club_id', 'team_id'], 'team' => ['team_id', 'club_id'], default => throw new \InvalidArgumentException('Organization type is invalid.') };
	}

	private function date(mixed $value): ?string
	{
		if ($value === null || $value === '') return null;
		$value = (string) $value; $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
		if (!$date || $date->format('Y-m-d') !== $value) throw new \InvalidArgumentException('History dates must use YYYY-MM-DD.');
		return $value;
	}

	private function text(mixed $value): ?string
	{
		$value = trim((string) ($value ?? '')); if ($value === '') return null;
		if (strlen($value) > 65535) throw new \LengthException('History notes are too long.');
		return $value;
	}
}
