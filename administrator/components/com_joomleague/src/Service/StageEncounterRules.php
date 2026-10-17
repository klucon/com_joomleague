<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Service;
defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Stage decisions override project defaults without modifying pinned profiles. */
final class StageEncounterRules
{
	public function __construct(private readonly DatabaseInterface $database) {}

	public static function policy(mixed $policy): string
	{
		if (!is_string($policy) || !in_array($policy, ['inherit', 'allow_draw', 'require_penalties'], true)) {
			throw new MatchResultValidationException('COM_JOOMLEAGUE_STAGE_ERROR_DECISION_POLICY');
		}
		return $policy;
	}

	public static function apply(array $profile, array $metadata): array
	{
		$policy = self::policy($metadata['encounter_decision_policy'] ?? 'inherit');
		if ($policy === 'inherit') return $profile;
		if (!($profile['match']['encounter']['penalty_series']['enabled'] ?? false)) {
			throw new MatchResultValidationException('COM_JOOMLEAGUE_STAGE_ERROR_DECISION_UNSUPPORTED');
		}
		$profile['match']['encounter']['penalty_series']['required'] = $policy === 'require_penalties';
		return $profile;
	}

	public function resolve(array $profile, int $projectId, ?int $stageId): array
	{
		if (!$stageId) return $profile;
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('metadata_json'))
			->from($this->database->quoteName('#__joomleague_project_stage'))
			->where('id = :stage')->where('project_id = :project')
			->bind(':stage', $stageId, ParameterType::INTEGER)
			->bind(':project', $projectId, ParameterType::INTEGER);
		$row = $this->database->setQuery($query)->loadAssoc();
		if (!$row) throw new MatchResultValidationException('COM_JOOMLEAGUE_ERROR_STAGE_PROJECT_INVALID');
		$metadata = empty($row['metadata_json']) ? [] : json_decode($row['metadata_json'], true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($metadata)) throw new \UnexpectedValueException('Stage metadata must be an object.');
		return self::apply($profile, $metadata);
	}
}
