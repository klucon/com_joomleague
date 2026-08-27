<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\StandingsCascadeTrigger;

final class StageentriesModel extends BaseDatabaseModel
{
	public function getStage(int $stageId): object
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['stage.*', $db->quoteName('project.name', 'project_name')])
			->from($db->quoteName('#__joomleague_project_stage', 'stage'))
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = stage.project_id')
			->where($db->quoteName('stage.id') . ' = :stageId')
			->bind(':stageId', $stageId, ParameterType::INTEGER);
		$stage = $db->setQuery($query)->loadObject();

		if (!$stage) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_INVALID'));

		return $stage;
	}

	/** @return list<object> */
	public function getEntries(int $stageId): array
	{
		$stage = $this->getStage($stageId);
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['entry.id', 'entry.entry_kind', 'entry.display_name', 'entry.entry_code', 'entry.seed_number', 'entry.published', 'team.name AS team_name', 'person.first_name', 'person.last_name', 'CASE WHEN link.entry_id IS NULL THEN 0 ELSE 1 END AS assigned'])
			->from($db->quoteName('#__joomleague_project_entry', 'entry'))
			->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
			->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
			->leftJoin($db->quoteName('#__joomleague_stage_entry', 'link') . ' ON link.entry_id = entry.id AND link.stage_id = ' . (int) $stageId)
			->where($db->quoteName('entry.project_id') . ' = :projectId')
			->bind(':projectId', $stage->project_id, ParameterType::INTEGER)
			->order([$db->quoteName('entry.ordering') . ' ASC', $db->quoteName('entry.id') . ' ASC']);

		return $db->setQuery($query)->loadObjectList();
	}

	public function saveAssignments(int $stageId, string $mode, array $entryIds): void
	{
		if (!in_array($mode, ['inherit_project', 'explicit'], true)) throw new \InvalidArgumentException(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_ENTRY_MODE_INVALID'));

		$stage = $this->getStage($stageId);
		$entryIds = array_values(array_unique(array_filter(array_map('intval', $entryIds), static fn (int $id): bool => $id > 0)));
		$db = $this->getDatabase();

		if ($mode === 'explicit' && $entryIds !== []) {
			$query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_project_entry'))
				->where($db->quoteName('project_id') . ' = :projectId')->whereIn($db->quoteName('id'), $entryIds)
				->bind(':projectId', $stage->project_id, ParameterType::INTEGER);
			if ((int) $db->setQuery($query)->loadResult() !== count($entryIds)) throw new \InvalidArgumentException(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_ENTRY_PROJECT_MISMATCH'));
		}

		$db->transactionStart();
		try {
			$query = $db->getQuery(true)->update($db->quoteName('#__joomleague_project_stage'))
				->set($db->quoteName('entry_selection_mode') . ' = :mode')->where($db->quoteName('id') . ' = :stageId')
				->bind(':mode', $mode)->bind(':stageId', $stageId, ParameterType::INTEGER);
			$db->setQuery($query)->execute();
			if ($mode === 'inherit_project') {
				$query = $db->getQuery(true)->delete($db->quoteName('#__joomleague_stage_transition_assignment'))->where($db->quoteName('target_stage_id') . ' = :stageId')->bind(':stageId', $stageId, ParameterType::INTEGER);
				$db->setQuery($query)->execute();
				$query = $db->getQuery(true)->delete($db->quoteName('#__joomleague_stage_entry'))->where($db->quoteName('stage_id') . ' = :stageId')->bind(':stageId', $stageId, ParameterType::INTEGER);
				$db->setQuery($query)->execute();
			} else {
				$query = $db->getQuery(true)->delete($db->quoteName('#__joomleague_stage_entry'))->where($db->quoteName('stage_id') . ' = :stageId')->where($db->quoteName('manual_assignment') . ' = 1')->bind(':stageId', $stageId, ParameterType::INTEGER);
				$db->setQuery($query)->execute();
				$userId = (int) (Factory::getApplication()->getIdentity()?->id ?? 0);
				foreach ($entryIds as $ordering => $entryId) {
					$query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_stage_entry'))->where('stage_id = :stage')->where('entry_id = :entry')->bind(':stage',$stageId,ParameterType::INTEGER)->bind(':entry',$entryId,ParameterType::INTEGER);
					if ((int) $db->setQuery($query)->loadResult() > 0) {
						$query = $db->getQuery(true)->update($db->quoteName('#__joomleague_stage_entry'))->set($db->quoteName('manual_assignment') . ' = 1')->set($db->quoteName('ordering') . ' = :ordering')->where('stage_id = :stage')->where('entry_id = :entry')->bind(':ordering',$ordering,ParameterType::INTEGER)->bind(':stage',$stageId,ParameterType::INTEGER)->bind(':entry',$entryId,ParameterType::INTEGER);
					} else {
						$query = $db->getQuery(true)->insert($db->quoteName('#__joomleague_stage_entry'))->columns($db->quoteName(['stage_id','entry_id','project_id','ordering','manual_assignment','created_by']))->values(implode(',', [(int)$stageId,(int)$entryId,(int)$stage->project_id,(int)$ordering,1,$userId]));
					}
					$db->setQuery($query)->execute();
				}
			}
			$db->transactionCommit();
		} catch (\Throwable $error) {
			$db->transactionRollback();
			throw $error;
		}

		(new StandingsCascadeTrigger($db))->triggerStage(
			(int) $stage->project_id,
			(int) $stageId,
			(int) (Factory::getApplication()->getIdentity()?->id ?? 0)
		);
	}
}
