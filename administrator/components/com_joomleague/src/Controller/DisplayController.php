<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\View\ViewInterface;
use Joomla\CMS\Router\Route;

final class DisplayController extends BaseController
{
	protected $default_view = 'dashboard';

	public function display($cachable = false, $urlparams = [])
	{
		if ($this->input->getCmd('view') === 'settings') {
			$this->setRedirect(Route::_('index.php?option=com_config&view=component&component=com_joomleague', false));

			return $this;
		}

		return parent::display($cachable, $urlparams);
	}

	private const SECTION_VIEWS = [
		'settings',
	];

	private const PROJECT_ASSIGNMENT_VIEWS = ['projectpositions', 'projectteams', 'projectreferees'];
	private const PROJECT_CONTEXT_VIEWS = ['rounds', 'divisions', 'templates'];
	private const MATCH_CONTEXT_VIEWS = ['matchdata'];
	private const SCHEDULE_VIEWS = ['schedule'];

	private function getProjectIdFromInput(): int
	{
		$projectId = $this->input->getInt('project_id');

		if ($projectId < 1) {
			$pid = $this->input->get('pid', [], 'array');
			$projectId = (int) ($pid[0] ?? 0);
		}

		return $projectId;
	}

	protected function prepareViewModel(ViewInterface $view): void
	{
		if (in_array($view->getName(), self::PROJECT_ASSIGNMENT_VIEWS, true)) {
			$model = $this->getModel('Projectsetup', '', ['base_path' => $this->basePath]);
			if ($model !== false) {
				$projectId = $this->getProjectIdFromInput();

				if ($projectId > 0) {
					$this->app->setUserState('com_joomleague.project_context.project_id', $projectId);
				} else {
					$projectId = (int) $this->app->getUserState('com_joomleague.project_context.project_id');
				}

				$model->setState('project_id', $projectId);
				$view->setModel($model, true);
			}
			return;
		}

		if (in_array($view->getName(), self::PROJECT_CONTEXT_VIEWS, true)) {
			parent::prepareViewModel($view);
			$model = $view->getModel();

			if ($model !== null && method_exists($model, 'setState')) {
				$projectId = $this->getProjectIdFromInput();

				if ($projectId > 0) {
					$this->app->setUserState('com_joomleague.project_context.project_id', $projectId);
				} else {
					$projectId = (int) $this->app->getUserState('com_joomleague.project_context.project_id');
				}

				$model->setState('filter.project_id', $projectId);
			}

			return;
		}

		if (in_array($view->getName(), self::MATCH_CONTEXT_VIEWS, true)) {
			parent::prepareViewModel($view);
			$model = $view->getModel();

			if ($model !== null && method_exists($model, 'setState')) {
				$model->setState('match_id', $this->input->getInt('match_id'));
				$model->setState('section', $this->input->getCmd('section', 'events'));
			}

			return;
		}

		if (in_array($view->getName(), self::SCHEDULE_VIEWS, true)) {
			parent::prepareViewModel($view);
			$model = $view->getModel();

			if ($model !== null && method_exists($model, 'setState')) {
				$projectId = $this->getProjectIdFromInput();

				if ($projectId > 0) {
					$this->app->setUserState('com_joomleague.project_context.project_id', $projectId);
				} else {
					$projectId = (int) $this->app->getUserState('com_joomleague.project_context.project_id');
				}

				$model->setState('project_id', $projectId);
			}

			return;
		}

		if (in_array($view->getName(), self::SECTION_VIEWS, true) && method_exists($view, 'setModel')) {
			$model = $this->getModel('Dashboard', '', ['base_path' => $this->basePath]);

			if ($model !== false) {
				$view->setModel($model, true);
			}

			return;
		}

		parent::prepareViewModel($view);
	}
}
