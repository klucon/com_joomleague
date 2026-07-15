<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Form\Form;

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
			$projectteamId = $this->application->getInput()->getInt('projectteam_id', 0);

			if ($projectteamId > 0) {
				$this->application->setUserState('com_joomleague.teamstaffs.projectteam_id', $projectteamId);
			} else {
				$projectteamId = (int) $this->application->getUserState('com_joomleague.teamstaffs.projectteam_id');
			}

			$item->projectteam_id = $projectteamId;
		}

		return $item;
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		$form = parent::getForm($data, $loadData);

		if ($form instanceof Form) {
			$this->scopeProjectTeamField($form);
		}

		return $form;
	}

	/**
	 * Pole "Týmy projektu" ve formuláři je jinak statický seznam VŠECH týmů napříč
	 * celým systémem (stovky položek). Pokud je z kontextu (URL, editovaný záznam
	 * nebo zapamatovaný filtr seznamu) známý konkrétní projekt, zúží se nabídka jen
	 * na jeho přiřazené týmy.
	 */
	private function scopeProjectTeamField(Form $form): void
	{
		$projectId = $this->resolveProjectId();

		if ($projectId < 1) {
			return;
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('pt.id, t.name AS title')
			->from('#__joomleague_project_team pt')
			->join('INNER', '#__joomleague_team t ON t.id = pt.team_id')
			->where('pt.project_id = ' . $projectId)
			->order('pt.ordering ASC, t.name ASC');

		$form->setFieldAttribute('projectteam_id', 'query', (string) $query);
	}

	private function resolveProjectId(): int
	{
		$db = $this->getDatabase();
		$itemId = $this->application->getInput()->getInt('id', 0);

		if ($itemId > 0) {
			return (int) $db->setQuery(
				'SELECT pt.project_id FROM #__joomleague_team_staff ts'
				. ' JOIN #__joomleague_project_team pt ON pt.id = ts.projectteam_id'
				. ' WHERE ts.id = ' . $itemId
			)->loadResult();
		}

		$projectteamId = (int) $this->application->getUserState('com_joomleague.teamstaffs.projectteam_id');

		if ($projectteamId > 0) {
			return (int) $db->setQuery(
				'SELECT project_id FROM #__joomleague_project_team WHERE id = ' . $projectteamId
			)->loadResult();
		}

		return (int) $this->application->getUserState('com_joomleague.teamstaffs.project_id');
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
