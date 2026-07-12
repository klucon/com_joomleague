<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class TemplateController extends EntityFormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_TEMPLATE';
	protected $view_list = 'templates';

	protected function preSaveHook(BaseDatabaseModel $model, array $validData = []): array
	{
		$projectId = (int) ($validData['project_id'] ?? 0);

		if ($projectId > 0) {
			$this->app->setUserState('com_joomleague.templates.project_id', $projectId);
		}

		return $validData;
	}

	protected function getRedirectToListAppend()
	{
		$append = parent::getRedirectToListAppend();
		$projectId = $this->input->getInt('project_id');

		if ($projectId < 1) {
			$form = $this->input->post->get('jform', [], 'array');
			$projectId = (int) ($form['project_id'] ?? 0);
		}

		return $projectId > 0 ? $append . '&project_id=' . $projectId : $append;
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		$form = $this->input->post->get('jform', [], 'array');
		$projectId = (int) ($form['project_id'] ?? $this->input->getInt('project_id'));

		return $projectId > 0 ? $append . '&project_id=' . $projectId : $append;
	}
}
