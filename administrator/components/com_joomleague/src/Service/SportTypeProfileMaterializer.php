<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class SportTypeProfileMaterializer
{
	private const CODE_PATTERN = '/^[a-z][a-z0-9_]{0,99}$/';

	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	/**
	 * The caller owns the transaction containing both the sport type and these rows.
	 *
	 * @param array{positions: bool, event_types: bool, statistics: bool} $options
	 * @return array{positions: int, event_types: int, statistics: int}
	 */
	public function materialize(int $sportTypeId, int $profileVersionId, array $options, int $actorId): array
	{
		if ($sportTypeId < 1 || $profileVersionId < 1) {
			throw new \InvalidArgumentException('A sport type and profile version are required.');
		}

		$profile = $this->loadProfile($profileVersionId);
		$definitions = [
			'positions' => $this->validateDefinitions($profile['positions'] ?? [], 'positions'),
			'event_types' => $this->validateDefinitions($profile['event_types'] ?? ($profile['events'] ?? []), 'event_types'),
			'statistics' => $this->validateDefinitions($profile['statistics'] ?? [], 'statistics'),
		];

		$counts = ['positions' => 0, 'event_types' => 0, 'statistics' => 0];
		$now = gmdate('Y-m-d H:i:s');

		if ($options['positions']) {
			foreach ($definitions['positions'] as $ordering => $definition) {
				$this->insertPosition($sportTypeId, $profileVersionId, $definition, $ordering + 1, $actorId, $now);
				$counts['positions']++;
			}
		}

		if ($options['event_types']) {
			foreach ($definitions['event_types'] as $ordering => $definition) {
				$this->insertEventType($sportTypeId, $profileVersionId, $definition, $ordering + 1, $actorId, $now);
				$counts['event_types']++;
			}
		}

		if ($options['statistics']) {
			foreach ($definitions['statistics'] as $ordering => $definition) {
				$this->insertStatistic($sportTypeId, $profileVersionId, $definition, $ordering + 1, $actorId, $now);
				$counts['statistics']++;
			}
		}

		return $counts;
	}

	/** @return array<string, mixed> */
	private function loadProfile(int $profileVersionId): array
	{
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('payload_json'))
			->from($this->database->quoteName('#__joomleague_sport_profile_version'))
			->where($this->database->quoteName('id') . ' = :profileVersionId')
			->bind(':profileVersionId', $profileVersionId, ParameterType::INTEGER);
		$payload = $this->database->setQuery($query)->loadResult();

		if (!is_string($payload) || $payload === '') {
			throw new \RuntimeException('The selected sport profile version has no payload.');
		}

		$profile = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($profile) || array_is_list($profile)) {
			throw new \RuntimeException('The selected sport profile payload must be an object.');
		}

		return $profile;
	}

	/** @return list<array<string, mixed>> */
	private function validateDefinitions(mixed $definitions, string $section): array
	{
		if (!is_array($definitions) || !array_is_list($definitions)) {
			throw new \RuntimeException(sprintf('Sport profile section %s must be a list.', $section));
		}

		$codes = [];
		foreach ($definitions as $definition) {
			if (!is_array($definition) || array_is_list($definition)) {
				throw new \RuntimeException(sprintf('Every %s definition must be an object.', $section));
			}

			$code = $definition['code'] ?? null;
			if (!is_string($code) || preg_match(self::CODE_PATTERN, $code) !== 1 || isset($codes[$code])) {
				throw new \RuntimeException(sprintf('Sport profile section %s contains an invalid or duplicate code.', $section));
			}

			$codes[$code] = true;
		}

		return $definitions;
	}

	/** @param array<string, mixed> $definition */
	private function insertPosition(int $sportTypeId, int $profileVersionId, array $definition, int $ordering, int $actorId, string $now): void
	{
		$personType = $this->nullableString($definition['person_type'] ?? null);
		if ($personType === null) {
			throw new \RuntimeException('Every position requires a person_type.');
		}

		$row = (object) [
			'uuid' => UuidFactory::v4(), 'sport_type_id' => $sportTypeId, 'source_profile_version_id' => $profileVersionId,
			'code' => $definition['code'], 'name' => '', 'name_key' => $this->nullableString($definition['name_key'] ?? null),
			'person_type' => $personType, 'lineup_group' => $this->nullableString($definition['lineup_group'] ?? null),
			'parent_id' => null, 'has_events' => $this->nullableBool($definition, 'has_events'),
			'has_statistics' => $this->nullableBool($definition, 'has_statistics'), 'source' => 'profile',
			'source_checksum' => CanonicalJson::checksum($definition), 'published' => 1, 'ordering' => $ordering,
			'created' => $now, 'created_by' => $actorId,
		];
		$this->database->insertObject('#__joomleague_sport_position', $row, 'id');
	}

	/** @param array<string, mixed> $definition */
	private function insertEventType(int $sportTypeId, int $profileVersionId, array $definition, int $ordering, int $actorId, string $now): void
	{
		$scoreDelta = $definition['score_delta'] ?? ($definition['points'] ?? null);
		if ($scoreDelta !== null && !is_int($scoreDelta) && !is_float($scoreDelta)) {
			throw new \RuntimeException('Event score_delta must be numeric.');
		}

		$row = (object) [
			'uuid' => UuidFactory::v4(), 'sport_type_id' => $sportTypeId, 'source_profile_version_id' => $profileVersionId,
			'code' => $definition['code'], 'name' => '', 'name_key' => $this->nullableString($definition['name_key'] ?? null),
			'person_type' => $this->nullableString($definition['person_type'] ?? null), 'timeline' => $this->bool($definition, 'timeline'),
			'direction' => (int) ($definition['direction'] ?? 0), 'affects_score' => $this->bool($definition, 'affects_score'),
			'score_delta' => $scoreDelta, 'score_target' => $this->nullableString($definition['score_target'] ?? null),
			'requires_second_person' => $this->bool($definition, 'requires_second_person'),
			'leads_to_suspension' => $this->bool($definition, 'leads_to_suspension'), 'system_event' => $this->bool($definition, 'system_event'),
			'source' => 'profile', 'source_checksum' => CanonicalJson::checksum($definition),
			'metadata_json' => CanonicalJson::encodeObject($definition), 'published' => 1, 'ordering' => $ordering,
			'created' => $now, 'created_by' => $actorId,
		];
		$this->database->insertObject('#__joomleague_event_type', $row, 'id');
	}

	/** @param array<string, mixed> $definition */
	private function insertStatistic(int $sportTypeId, int $profileVersionId, array $definition, int $ordering, int $actorId, string $now): void
	{
		$scope = $this->nullableString($definition['scope'] ?? null);
		if ($scope === null) {
			throw new \RuntimeException('Every statistic requires a scope.');
		}
		$type = (string) ($definition['statistic_type'] ?? ($definition['type'] ?? 'basic'));
		$calculationSource = (string) ($definition['source'] ?? ($type === 'calculated' ? 'calculated' : 'manual'));

		$row = (object) [
			'uuid' => UuidFactory::v4(), 'sport_type_id' => $sportTypeId, 'source_profile_version_id' => $profileVersionId,
			'code' => $definition['code'], 'name' => '', 'name_key' => $this->nullableString($definition['name_key'] ?? null),
			'abbreviation_key' => $this->nullableString($definition['abbreviation_key'] ?? null), 'statistic_type' => $type,
			'scope' => $scope, 'value_type' => (string) ($definition['value_type'] ?? 'integer'),
			'calculation_source' => $calculationSource, 'source' => 'profile', 'source_checksum' => CanonicalJson::checksum($definition),
			'metadata_json' => CanonicalJson::encodeObject($definition), 'published' => 1, 'ordering' => $ordering,
			'created' => $now, 'created_by' => $actorId,
		];
		$this->database->insertObject('#__joomleague_statistic', $row, 'id');
	}

	/** @param array<string, mixed> $definition */
	private function bool(array $definition, string $key): int
	{
		return !empty($definition[$key]) ? 1 : 0;
	}

	/** @param array<string, mixed> $definition */
	private function nullableBool(array $definition, string $key): ?int
	{
		return array_key_exists($key, $definition) ? $this->bool($definition, $key) : null;
	}

	private function nullableString(mixed $value): ?string
	{
		return is_string($value) && $value !== '' ? $value : null;
	}
}
