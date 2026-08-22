<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\CMS\Language\Text;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultSummaryProvider;
use Joomleague\Component\Joomleague\Administrator\Service\MatchBatchEditor;
use Joomleague\Component\Joomleague\Administrator\Service\MatchParticipantSummaryProvider;
use Joomleague\Component\Joomleague\Administrator\Service\MatchScheduleEditor;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository;
use Joomleague\Component\Joomleague\Administrator\Service\StageEntryOptionsProvider;

final class MatchesModel extends ListModel
{
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= ['id', 'a.id', 'match_number', 'a.match_number', 'scheduled_start', 'a.scheduled_start', 'published', 'a.published', 'ordering', 'a.ordering'];
		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.id', $direction = 'desc'): void
	{
		$this->setState('round_id', $this->getUserStateFromRequest($this->context . '.round_id', 'round_id', 0, 'uint'));
		foreach (['search', 'published', 'status_code'] as $filter) {
			$this->setState('filter.' . $filter, $this->getUserStateFromRequest($this->context . '.filter.' . $filter, 'filter_' . $filter, ''));
		}
		parent::populateState($ordering, $direction);
	}

	public function getRound(): object
	{
		$db = $this->getDatabase(); $roundId = (int) $this->getState('round_id');
		$query = $db->getQuery(true)->select(['r.*', 'stage.name AS stage_name', 'project.name AS project_name', 'project.timezone AS project_timezone'])
			->from($db->quoteName('#__joomleague_project_round', 'r'))->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id = r.stage_id')->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = r.project_id')
			->where($db->quoteName('r.id') . ' = :roundId')->bind(':roundId', $roundId, ParameterType::INTEGER);
		$round = $db->setQuery($query)->loadObject();
		if (!$round) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_ROUND_INVALID'));
		return $round;
	}

	protected function getListQuery()
	{
		$db = $this->getDatabase(); $roundId = (int) $this->getState('round_id'); $query = $db->getQuery(true)->select('a.*')->select($db->quoteName('editor.name', 'editor_name'))
			->select([
				$db->quoteName('result.result_type'),
				$db->quoteName('result.status_code', 'result_status_code'),
				$db->quoteName('result.outcome_code', 'result_outcome_code'),
			])
			->from($db->quoteName('#__joomleague_project_match', 'a'))->leftJoin($db->quoteName('#__users', 'editor') . ' ON editor.id = a.checked_out')
			->leftJoin($db->quoteName('#__joomleague_match_result', 'result') . ' ON result.match_id = a.id')
			->where($db->quoteName('a.round_id') . ' = :roundId')->bind(':roundId', $roundId, ParameterType::INTEGER);
		$published = $this->getState('filter.published');
		if ($published !== '') { $published = (int) $published; $query->where('a.published = :published')->bind(':published', $published, ParameterType::INTEGER); }
		$status = trim((string) $this->getState('filter.status_code'));
		if ($status !== '') $query->where('a.status_code = :status')->bind(':status', $status);
		$search = trim((string) $this->getState('filter.search'));
		if ($search !== '') {
			if (str_starts_with($search, 'id:')) { $id = (int) substr($search, 3); $query->where('a.id = :id')->bind(':id', $id, ParameterType::INTEGER); }
			else { $search = '%' . $search . '%'; $query->where('(a.code LIKE :code OR a.match_number LIKE :number OR a.contest_type LIKE :contest)')->bind(':code', $search)->bind(':number', $search)->bind(':contest', $search); }
		}
		$query->order($db->escape((string) $this->getState('list.ordering', 'a.id')) . ' ' . ($this->getState('list.direction') === 'desc' ? 'DESC' : 'ASC'));
		return $query;
	}

	/** @return array<int,object> */
	public function getItems(): array
	{
		$items = parent::getItems();

		if (!is_array($items) || $items === []) return is_array($items) ? $items : [];

		$ids = array_map(static fn (object $item): int => (int) $item->id, $items);
		$values = (new MatchResultSummaryProvider($this->getDatabase()))->loadRootValues($ids);
		$participants = (new MatchParticipantSummaryProvider($this->getDatabase()))->loadDetails($ids);
		$round = $this->getRound();

		foreach ($items as $item) {
			$item->result_values = $values[(int) $item->id] ?? [];
			$item->participant_details = $participants[(int) $item->id] ?? [];
			$item->participant_names = array_column($item->participant_details, 'name');
			$item->participant_entries = [];
			foreach ($item->participant_details as $participant) $item->participant_entries[(int) $participant['slot_number']] = (int) $participant['entry_id'];
			[$item->scheduled_date_local, $item->scheduled_time_local] = $this->dateParts($item->scheduled_start, $item->timezone, $round);
		}

		return $items;
	}

	/** @return list<object> */
	public function getEntryOptions(): array
	{
		$round = $this->getRound();
		return (new StageEntryOptionsProvider($this->getDatabase()))->load((int) $round->project_id, (int) $round->stage_id);
	}

	/**
	 * The contest type is fixed per project (derived from its sport profile) and applies to
	 * every match in every round of that project - it is never chosen per match. "head_to_head"
	 * is the exactly-two-participants format most sports use; anything else (currently only
	 * "race") can have any number of participants and is managed on a dedicated screen instead
	 * of the inline home/away columns.
	 */
	public function getContestType(): string
	{
		$round = $this->getRound();
		$project = (new ProjectContextRepository($this->getDatabase()))->get((int) $round->project_id);

		return (string) ($project->profile['contest']['type'] ?? 'head_to_head');
	}

	/** @param array<string,mixed> $data */
	public function saveInline(int $matchId, int $roundId, array $data, int $userId): void
	{
		(new MatchScheduleEditor($this->getDatabase()))->save($matchId, $roundId, $data, $userId);
	}

	/**
	 * @param list<int> $matchIds
	 * @return array{applied:int,skipped:int}
	 */
	public function batchApply(array $matchIds, int $roundId, ?int $venueId, ?int $shiftDays, int $userId): array
	{
		return (new MatchBatchEditor($this->getDatabase()))->apply($matchIds, $roundId, $venueId, $shiftDays, $userId);
	}

	/** @return list<object> */
	public function getVenueOptions(): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)->select([$db->quoteName('id', 'value'), $db->quoteName('name', 'text')])
			->from($db->quoteName('#__joomleague_venue'))->where($db->quoteName('published') . ' = 1')->order($db->quoteName('name'));
		return $db->setQuery($query)->loadObjectList();
	}

	/** @return array{string,string} */
	private function dateParts(?string $utc, ?string $override, object $round): array
	{
		if (!$utc) return ['', ''];
		$timezone = $override ?: ($round->project_timezone ?: (string) Factory::getApplication()->get('offset', 'UTC'));
		$date = Factory::getDate($utc, 'UTC')->setTimezone(new \DateTimeZone($timezone));
		return [$date->format('Y-m-d'), $date->format('H:i')];
	}

	public function displayResultSummary(object $item): string
	{
		return (new MatchResultSummaryProvider($this->getDatabase()))->format((string) ($item->result_type ?? ''), $item->result_values ?? []);
	}

	public function displayDate(?string $utc, ?string $override, object $round): string
	{
		if (!$utc) return '';
		$timezone = $override ?: ($round->project_timezone ?: (string) Factory::getApplication()->get('offset', 'UTC'));
		return Factory::getDate($utc, 'UTC')->setTimezone(new \DateTimeZone($timezone))->format(Text::_('COM_JOOMLEAGUE_DATETIME_FORMAT'));
	}
}
