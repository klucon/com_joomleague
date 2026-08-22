<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository;
use Joomleague\Component\Joomleague\Administrator\Service\MatchParticipantSummaryProvider;
use Joomleague\Component\Joomleague\Administrator\Service\MatchScheduleEditor;

final class MatchModel extends AdminModel implements CurrentUserInterface
{
	use CurrentUserTrait;
	protected $text_prefix = 'COM_JOOMLEAGUE_MATCH';
	public function getTable($name = 'Match', $prefix = 'Administrator', $options = []): Table { return parent::getTable($name, $prefix, $options); }
	public function getForm($data = [], $loadData = true): Form|false { return $this->loadForm('com_joomleague.match', 'match', ['control' => 'jform', 'load_data' => $loadData]); }
	protected function loadFormData(): array|object
	{
		$data = Factory::getApplication()->getUserState('com_joomleague.edit.match.data', []); if ($data) return $data;
		$item = $this->getItem(); if ((int) ($item->round_id ?? 0) < 1) $item->round_id = Factory::getApplication()->getInput()->getInt('round_id');
		$round = $this->getRound((int) $item->round_id); $item->project_id = $round->project_id; $item->stage_id = $round->stage_id;
		$item->contest_type = $this->projectContestType((int) $round->project_id);
		if (!empty($item->scheduled_start)) {
			$local = Factory::getDate($item->scheduled_start, 'UTC')->setTimezone(new \DateTimeZone($this->effectiveTimezone((string) ($item->timezone ?? ''), $round)));
			$item->scheduled_date = $local->format('Y-m-d'); $item->scheduled_time = $local->format('H:i');
		}
		if ((int) ($item->id ?? 0) > 0) {
			$details = (new MatchParticipantSummaryProvider($this->getDatabase()))->loadDetails([(int) $item->id])[(int) $item->id] ?? [];
			foreach ($details as $participant) $item->{'participant_slot_' . (int) $participant['slot_number']} = (int) $participant['entry_id'];
		}
		return $item;
	}
	public function getRound(?int $roundId = null): object
	{
		if ($roundId === null) { $item = $this->getItem(); $roundId = (int) ($item->round_id ?? Factory::getApplication()->getInput()->getInt('round_id')); }
		$db = $this->getDatabase(); $query = $db->getQuery(true)->select(['r.*', 'stage.name AS stage_name', 'project.name AS project_name', 'project.timezone AS project_timezone'])
			->from($db->quoteName('#__joomleague_project_round', 'r'))->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id = r.stage_id')->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = r.project_id')
			->where($db->quoteName('r.id') . ' = :roundId')->bind(':roundId', $roundId, ParameterType::INTEGER);
		$round = $db->setQuery($query)->loadObject(); if (!$round) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_ROUND_INVALID')); return $round;
	}
	public function save($data): bool
	{
		$schedule = [
			'participant_slot_1' => $data['participant_slot_1'] ?? 0,
			'participant_slot_2' => $data['participant_slot_2'] ?? 0,
			'scheduled_date' => $data['scheduled_date'] ?? '',
			'scheduled_time' => $data['scheduled_time'] ?? '',
			'match_number' => $data['match_number'] ?? '',
			'attendance' => $data['attendance'] ?? '',
		];
		$id = (int) ($data['id'] ?? 0); $owner = $id > 0 ? $this->storedOwner($id) : $this->getRound((int) ($data['round_id'] ?? 0));
		$data['round_id'] = (int) ($owner->round_id ?? $owner->id); $data['stage_id'] = (int) $owner->stage_id; $data['project_id'] = (int) $owner->project_id;
		$data['contest_type'] = $this->projectContestType((int) $owner->project_id);
		if (($schedule['scheduled_date'] === '') !== ($schedule['scheduled_time'] === '')) throw new \UnexpectedValueException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_VALUES_INVALID'));
		$data['scheduled_start'] = $schedule['scheduled_date'] !== '' ? Factory::getDate($schedule['scheduled_date'] . ' ' . $schedule['scheduled_time'] . ':00', $this->effectiveTimezone((string) ($data['timezone'] ?? ''), $owner))->setTimezone(new \DateTimeZone('UTC'))->toSql() : null;
		if (!parent::save($data)) return false;
		$savedId = $id > 0 ? $id : (int) $this->getState($this->getName() . '.id');
		(new MatchScheduleEditor($this->getDatabase()))->save($savedId, (int) $data['round_id'], $schedule, (int) $this->getCurrentUser()->id);
		return true;
	}
	protected function prepareTable($table): void
	{
		$now = Factory::getDate()->toSql(); $userId = (int) $this->getCurrentUser()->id;
		if ((int) $table->id === 0) { $table->uuid = UuidFactory::v4(); $table->created = $now; $table->created_by = $userId; $table->ordering = $table->ordering ?: $table->getNextOrder('round_id = ' . (int) $table->round_id); }
		else { $table->modified = $now; $table->modified_by = $userId; }
	}
	private function storedOwner(int $id): object
	{
		$db = $this->getDatabase(); $query = $db->getQuery(true)->select(['match.round_id', 'match.stage_id', 'match.project_id', 'project.timezone AS project_timezone'])->from($db->quoteName('#__joomleague_project_match', 'match'))->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id')->where($db->quoteName('match.id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
		$owner = $db->setQuery($query)->loadObject(); if (!$owner) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_INVALID')); return $owner;
	}
	private function effectiveTimezone(string $matchTimezone, object $context): string { return $matchTimezone !== '' ? $matchTimezone : ((string) ($context->project_timezone ?? '') !== '' ? (string) $context->project_timezone : (string) Factory::getApplication()->get('offset', 'UTC')); }
	private function projectContestType(int $projectId): string
	{
		$project = (new ProjectContextRepository($this->getDatabase()))->get($projectId);
		$contestType = (string) ($project->profile['contest']['type'] ?? '');

		if (preg_match('/^[a-z][a-z0-9_]{0,99}$/', $contestType) !== 1) {
			throw new \UnexpectedValueException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_CONTEST_PROFILE_INVALID'));
		}

		return $contestType;
	}
}
