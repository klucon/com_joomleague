<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Service;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;

final class MatchQuickResultEditor
{
	public function __construct(private readonly DatabaseInterface $db) {}

	public static function supports(array $profile): bool
	{
		$schema = (new MatchResultEditorSchemaBuilder())->build($profile);
		return $schema['allow_aggregate_only'] && $schema['contest_type'] === 'head_to_head'
			&& in_array('final', $schema['statuses'], true) && in_array('completed', $schema['outcomes'], true);
	}

	public static function revision(int $matchId, int $roundId, array $profile, array $participantIds, string $version): string
	{
		return CanonicalJson::checksum(['match' => $matchId, 'round' => $roundId, 'profile' => $profile, 'participants' => array_values(array_map('intval', $participantIds)), 'version' => $version]);
	}

	public function version(int $matchId): string
	{
		$query = $this->db->getQuery(true)->select('id')->from($this->db->quoteName('#__joomleague_match_result'))->where('match_id=:matchId')->bind(':matchId', $matchId, ParameterType::INTEGER);
		return (string) $this->db->setQuery($query)->loadResult();
	}

	/** Uses the same repository, validation and locks as the full editor. */
	public function save(int $matchId, int $roundId, array $scores, string $revision): string
	{
		$repository = new MatchResultRepository($this->db);
		$reader = new MatchResultEditorContext($this->db, $repository);
		$assertContext = function (array $context) use ($matchId, $roundId, $revision): void {
			$user = Factory::getApplication()->getIdentity();
			if (!$user->id || !$user->authorise('joomleague.project.edit.results', 'com_joomleague.project.' . $context['project']['id'])) throw new MatchResultValidationException('JERROR_ALERTNOAUTHOR');
			$ids = array_column($context['participants'], 'id');
			if ($context['round']['id'] !== $roundId || count($ids) !== 2 || !self::supports($context['profile_payload'])
				|| array_column($context['participants'], 'slot_number') !== [1, 2]) throw new MatchResultValidationException('COM_JOOMLEAGUE_QUICK_RESULT_UNAVAILABLE');
			$current = $context['result'];
			if ($current !== null && (count($current['segments'][0]['values'] ?? []) !== 2 || array_diff(array_column($current['segments'][0]['values'], 'participant_id'), $ids) !== [])) throw new MatchResultValidationException('COM_JOOMLEAGUE_QUICK_RESULT_UNAVAILABLE');
			if ($current !== null && (($current['segments'][0]['children'] ?? []) !== [] || $current['status_code'] !== 'final' || $current['outcome_code'] !== 'completed')) throw new MatchResultValidationException('COM_JOOMLEAGUE_QUICK_RESULT_UNAVAILABLE');
			foreach ($current['segments'][0]['values'] ?? [] as $value) {
				if ($value['numeric_value'] === null || $value['text_value'] !== null || $value['status_code'] !== null || $value['result_rank'] !== null) throw new MatchResultValidationException('COM_JOOMLEAGUE_QUICK_RESULT_UNAVAILABLE');
			}
			$expected = self::revision($matchId, $roundId, $context['profile_payload'], $ids, $this->version($matchId));
			if (!hash_equals($expected, $revision)) throw new MatchResultValidationException('COM_JOOMLEAGUE_QUICK_RESULT_STALE');
		};
		$context = $reader->get($matchId);
		$assertContext($context);
		$ids = array_column($context['participants'], 'id');
		if (count($scores) !== 2 || array_diff(array_map('strval', array_keys($scores)), array_map('strval', $ids)) !== []) throw new MatchResultValidationException('COM_JOOMLEAGUE_QUICK_RESULT_VALUES_INVALID');
		$payload = $context['result'] ?? ['result_type' => 'numeric_score', 'status_code' => 'final', 'outcome_code' => 'completed', 'finalized_at' => Factory::getDate()->toSql(), 'notes' => null, 'metadata' => [], 'segments' => [['level_code' => 'result', 'sequence_number' => 1, 'values' => [], 'children' => []]]];
		$existing = array_column($payload['segments'][0]['values'], null, 'participant_id');
		$payload['segments'][0]['values'] = [];
		foreach ($ids as $id) {
			$value = $scores[$id] ?? null;
			if (!is_string($value) || strlen($value) > 64 || preg_match('/^\d+(?:\.\d+)?$/D', $value) !== 1) throw new MatchResultValidationException('COM_JOOMLEAGUE_QUICK_RESULT_VALUES_INVALID');
			$payload['segments'][0]['values'][] = array_replace($existing[$id] ?? ['participant_id' => $id, 'text_value' => null, 'status_code' => null, 'result_rank' => null, 'metadata' => []], ['numeric_value' => $value]);
		}
		$resultId = $repository->replace($matchId, $payload, (int) Factory::getApplication()->getIdentity()->id, function (array $match) use ($reader, $matchId, $assertContext): void {
			if ((int) $match['checked_out'] !== 0 && (int) $match['checked_out'] !== (int) Factory::getApplication()->getIdentity()->id) throw new MatchResultValidationException('COM_JOOMLEAGUE_QUICK_RESULT_CHECKED_OUT');
			$assertContext($reader->get($matchId));
		});
		return self::revision($matchId, $roundId, $context['profile_payload'], $ids, (string) $resultId);
	}
}
