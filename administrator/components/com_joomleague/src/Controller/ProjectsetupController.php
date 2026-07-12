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
	public function save(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$projectId = $this->input->getInt('project_id');
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
}
