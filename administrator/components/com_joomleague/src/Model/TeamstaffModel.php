<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;

final class TeamstaffModel extends EntityAdminModel
{
	protected string $entityName = 'teamstaff';

	private AdministratorApplication $application;

	public function setApplication(AdministratorApplication $application): void
	{
		$this->application = $application;
	}

	protected function loadFormData(): object
	{
		$item = $this->getItem();

		if ((int) $item->projectteam_id < 1) {
			$projectteamId = $this->application->getInput()->getInt('projectteam_id');

			if ($projectteamId > 0) {
				$this->application->setUserState('com_joomleague.teamstaffs.projectteam_id', $projectteamId);
			} else {
				$projectteamId = (int) $this->application->getUserState('com_joomleague.teamstaffs.projectteam_id');
			}

			$item->projectteam_id = $projectteamId;
		}

		return $item;
	}

	protected function prepareTable($table): void
	{
		foreach (['notes', 'picture', 'injury_detail', 'suspension_detail', 'away_detail', 'alias'] as $field) {
			$table->{$field} = trim((string) $table->{$field});
		}

		foreach (['projectteam_id', 'person_id', 'project_position_id', 'active', 'injury', 'suspension', 'away', 'published'] as $field) {
			$table->{$field} = (int) $table->{$field};
		}

		$table->project_position_id = $table->project_position_id ?: null;

		foreach (['injury_date_start', 'injury_date_end', 'susp_date_start', 'susp_date_end', 'away_date_start', 'away_date_end', 'checked_out_time', 'modified'] as $field) {
			$table->{$field} = trim((string) ($table->{$field} ?? '')) ?: null;
		}

		parent::prepareTable($table);
	}
}
