<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

final class ProjectsetupController extends BaseController
{
	public function searchteams(): void
	{
		if (!$this->checkAjaxAccess()) {
			return;
		}

		$projectId = $this->input->getInt('project_id', 0);

		if (!$this->canEditProject($projectId)) {
			$this->json(['error' => Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED')], 403);
			return;
		}

		$this->json([
			'items' => $this->getModel('Projectsetup')->searchAvailableTeams(
				$projectId,
				$this->input->getString('q', $this->input->getString('query', ''))
			),
		]);
	}

	public function addteam(): void
	{
		if (!$this->checkAjaxAccess()) {
			return;
		}

		$projectId = $this->input->getInt('project_id', 0);

		if (!$this->canEditProject($projectId)) {
			$this->json(['error' => Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED')], 403);
			return;
		}

		try {
			$row = $this->getModel('Projectsetup')->addTeam($projectId, $this->input->getInt('team_id', 0));
			$this->json(['item' => $this->serializeTeamAssignment($row)]);
		} catch (\Throwable $exception) {
			$this->json(['error' => Text::_($exception->getMessage())], 400);
		}
	}

	public function removeteam(): void
	{
		if (!$this->checkAjaxAccess()) {
			return;
		}

		$projectId = $this->input->getInt('project_id', 0);
		$assignmentId = $this->input->getInt('assignment_id', 0);

		if (!$this->canEditProject($projectId)) {
			$this->json(['error' => Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED')], 403);
			return;
		}

		try {
			$this->getModel('Projectsetup')->removeTeam($projectId, $assignmentId);
			$this->json(['ok' => true, 'assignment_id' => $assignmentId]);
		} catch (\Throwable $exception) {
			$this->json(['error' => Text::_($exception->getMessage())], 400);
		}
	}

	public function saveordering(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$projectId = $this->input->getInt('project_id', 0);

		if (!$this->canEditProject($projectId)) {
			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 403);
		}

		$this->getModel('Projectsetup')->saveTeamOrdering($projectId, $this->input->get('ordering', [], 'array'));

		$this->setRedirect(
			Route::_('index.php?option=com_joomleague&view=projectteams&project_id=' . $projectId, false),
			Text::_('COM_JOOMLEAGUE_PROJECT_TEAM_ORDERING_SAVED')
		);
	}

	public function save(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$projectId = $this->input->getInt('project_id', 0);
		$section = $this->input->getCmd('section');
		$assignedIds = $this->input->get('assigned', [], 'array');
		$ordering = $this->input->get('ordering', [], 'array');

		$map = [
			'positions' => ['method' => 'syncPositions', 'view' => 'projectpositions'],
			'teams' => ['method' => 'syncTeams', 'view' => 'projectteams'],
			'referees' => ['method' => 'syncReferees', 'view' => 'projectreferees'],
		];

		if ($projectId < 1 || !isset($map[$section])) {
			throw new \InvalidArgumentException(Text::_('COM_JOOMLEAGUE_PROJECT_SETUP_INVALID'));
		}

		if (!$this->app->getIdentity()->authorise('core.edit', 'com_joomleague.project.' . $projectId)) {
			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 403);
		}

		$model = $this->getModel('Projectsetup');
		$method = $map[$section]['method'];
		$model->$method($projectId, $assignedIds);

		if ($section === 'teams') {
			$model->saveTeamOrdering($projectId, $ordering);
		}

		$this->setRedirect(
			Route::_('index.php?option=com_joomleague&view=' . $map[$section]['view'] . '&project_id=' . $projectId, false),
			Text::_('COM_JOOMLEAGUE_PROJECT_ASSIGNMENTS_SAVED')
		);
	}

	private function canEditProject(int $projectId): bool
	{
		return $projectId > 0 && $this->app->getIdentity()->authorise('core.edit', 'com_joomleague.project.' . $projectId);
	}

	private function checkAjaxAccess(): bool
	{
		if (!Session::checkToken('request')) {
			$this->json(['error' => Text::_('JINVALID_TOKEN')], 403);
			return false;
		}

		return true;
	}

	private function serializeTeamAssignment(object $row): array
	{
		$assignmentId = (int) $row->assignment_id;

		return [
			'id' => (int) $row->id,
			'assignment_id' => $assignmentId,
			'name' => (string) $row->name,
			'ordering' => (int) $row->ordering,
			'player_count' => (int) ($row->player_count ?? 0),
			'staff_count' => (int) ($row->staff_count ?? 0),
			'edit_url' => Route::_('index.php?option=com_joomleague&task=projectteam.edit&id=' . $assignmentId, false),
			'players_url' => Route::_('index.php?option=com_joomleague&view=teamplayers&projectteam_id=' . $assignmentId, false),
			'staff_url' => Route::_('index.php?option=com_joomleague&view=teamstaffs&projectteam_id=' . $assignmentId, false),
		];
	}

	private function json(array $data, int $status = 200): void
	{
		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$this->app->setHeader('Status', (string) $status, true);
		http_response_code($status);
		$this->app->sendHeaders();
		echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$this->app->close();
	}
}
