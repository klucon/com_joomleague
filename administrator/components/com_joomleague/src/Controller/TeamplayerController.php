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
use Joomla\CMS\Session\Session;

final class TeamplayerController extends EntityFormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_TEAMPLAYER';
	protected $view_list = 'teamplayers';

	public function searchpersons(): void
	{
		if (!$this->checkAjaxAccess()) {
			return;
		}

		$model = $this->getModel('Teamplayers');
		$projectteamId = $this->input->getInt('projectteam_id', 0);

		if (!$this->canEditProjectTeam($model, $projectteamId)) {
			$this->json(['error' => Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED')], 403);
			return;
		}

		$this->json([
			'items' => $model->searchAvailablePersons(
				$projectteamId,
				$this->input->getString('q', $this->input->getString('query', ''))
			),
		]);
	}

	public function addperson(): void
	{
		if (!$this->checkAjaxAccess()) {
			return;
		}

		$model = $this->getModel('Teamplayers');
		$projectteamId = $this->input->getInt('projectteam_id', 0);

		if (!$this->canEditProjectTeam($model, $projectteamId)) {
			$this->json(['error' => Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED')], 403);
			return;
		}

		try {
			$row = $model->addPerson($projectteamId, $this->input->getInt('person_id', 0));
			$this->json(['item' => $this->serializeAssignment($row)]);
		} catch (\Throwable $exception) {
			$this->json(['error' => Text::_($exception->getMessage())], 400);
		}
	}

	private function canEditProjectTeam($model, int $projectteamId): bool
	{
		$projectId = $model->getProjectTeamProjectId($projectteamId);

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

	private function serializeAssignment(object $row): array
	{
		$label = (string) $row->name;

		if (trim((string) ($row->knvbnr ?? '')) !== '') {
			$label .= ' (' . (string) $row->knvbnr . ')';
		}

		return [
			'id' => (int) $row->id,
			'person_id' => (int) $row->person_id,
			'projectteam_id' => (int) $row->projectteam_id,
			'ordering' => (int) $row->ordering,
			'name' => $label,
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
