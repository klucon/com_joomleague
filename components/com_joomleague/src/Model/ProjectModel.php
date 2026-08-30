<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://joomleague.eu). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\ProjectNavigationReader;

final class ProjectModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$this->setState('project_id', Factory::getApplication()->getInput()->getInt('project_id', 0));
	}

	/** @return array<string,mixed> */
	public function getProject(): array
	{
		$app = Factory::getApplication();
		$app->bootComponent('com_joomleague');

		$data = (new ProjectNavigationReader(Factory::getContainer()->get(DatabaseInterface::class)))->forProject(
			(int) $this->getState('project_id'),
			$app->getIdentity()->getAuthorisedViewLevels()
		);

		if (isset($data['error'])) {
			$data['error'] = $data['error'] === 'project_required'
				? 'COM_JOOMLEAGUE_PROJECT_NO_PROJECT'
				: 'COM_JOOMLEAGUE_PROJECT_UNAVAILABLE';
		}

		return $data;
	}
}
