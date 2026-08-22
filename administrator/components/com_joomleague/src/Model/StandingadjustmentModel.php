<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\StandingsCascadeTrigger;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class StandingadjustmentModel extends AdminModel implements CurrentUserInterface
{
	use CurrentUserTrait;
	protected $text_prefix = 'COM_JOOMLEAGUE_STANDING_ADJUSTMENT';
	public function getTable($name = 'Standingadjustment', $prefix = 'Administrator', $options = []): Table { return parent::getTable($name, $prefix, $options); }
	public function getForm($data = [], $loadData = true): Form|false { return $this->loadForm('com_joomleague.standingadjustment', 'standingadjustment', ['control' => 'jform', 'load_data' => $loadData]); }

	protected function loadFormData(): array|object
	{
		$data = Factory::getApplication()->getUserState('com_joomleague.edit.standingadjustment.data', []); if ($data) return $data;
		$item = $this->getItem(); if ((int) ($item->project_id ?? 0) < 1) $item->project_id = Factory::getApplication()->getInput()->getInt('project_id'); if ((int) ($item->stage_id ?? 0) < 1) $item->stage_id = Factory::getApplication()->getInput()->getInt('stage_id') ?: null; return $item;
	}

	public function getProject(): object
	{
		$item = $this->getItem(); $projectId = (int) ($item->project_id ?? Factory::getApplication()->getInput()->getInt('project_id'));
		$query = $this->getDatabase()->getQuery(true)->select(['id', 'name'])->from($this->getDatabase()->quoteName('#__joomleague_project'))->where('id = :project')->bind(':project', $projectId, ParameterType::INTEGER);
		$project = $this->getDatabase()->setQuery($query)->loadObject(); if (!$project) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_STANDING_ADJUSTMENT_PROJECT_INVALID')); return $project;
	}

	public function save($data): bool
	{
		$id = (int) ($data['id'] ?? 0); if ($id > 0) { $query = $this->getDatabase()->getQuery(true)->select(['project_id', 'stage_id'])->from($this->getDatabase()->quoteName('#__joomleague_standing_adjustment'))->where('id = :id')->bind(':id', $id, ParameterType::INTEGER); $owner = $this->getDatabase()->setQuery($query)->loadObject(); if (!$owner) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_STANDING_ADJUSTMENT_INVALID')); $data['project_id'] = (int) $owner->project_id; $data['stage_id'] = $owner->stage_id === null ? null : (int) $owner->stage_id; }
		$result = parent::save($data);
		if ($result) $this->cascadeStandings((int) $data['project_id'], isset($data['stage_id']) && (int) $data['stage_id'] > 0 ? (int) $data['stage_id'] : null);
		return $result;
	}

	public function delete(&$pks): bool
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)->select(['project_id', 'stage_id'])->from($db->quoteName('#__joomleague_standing_adjustment'))->whereIn($db->quoteName('id'), array_map('intval', (array) $pks));
		$affected = $db->setQuery($query)->loadObjectList();

		$result = parent::delete($pks);

		if ($result) {
			$seen = [];
			foreach ($affected as $row) {
				$stageId = $row->stage_id === null ? null : (int) $row->stage_id;
				$key = $row->project_id . ':' . ($stageId ?? 0);
				if (isset($seen[$key])) continue;
				$seen[$key] = true;
				$this->cascadeStandings((int) $row->project_id, $stageId);
			}
		}

		return $result;
	}

	/**
	 * Republishes standings after a correction (create/edit/delete) — an
	 * adjustment doesn't go through a match result save, so it needs its
	 * own cascade trigger to stay "no manual recalculate needed" for admins.
	 */
	private function cascadeStandings(int $projectId, ?int $stageId): void
	{
		try {
			(new StandingsCascadeTrigger($this->getDatabase()))->trigger($projectId, $stageId, (int) $this->getCurrentUser()->id);
		} catch (\Throwable $exception) {
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague.standings');
		}
	}

	protected function prepareTable($table): void
	{
		$now = Factory::getDate()->toSql(); $userId = (int) $this->getCurrentUser()->id;
		if ((int) $table->id === 0) { $table->uuid = UuidFactory::v4(); $table->created = $now; $table->created_by = $userId; $table->ordering = $table->ordering ?: $table->getNextOrder('project_id = ' . (int) $table->project_id . ' AND stage_key = ' . (int) ($table->stage_id ?? 0)); } else { $table->modified = $now; $table->modified_by = $userId; }
	}
}
