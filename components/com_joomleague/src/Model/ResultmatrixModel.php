<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\ResultMatrixReader;

final class ResultmatrixModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$input = Factory::getApplication()->getInput();
		$this->setState('project_id', $input->getInt('project_id', 0));
		$stageId = $input->getInt('stage_id', 0);
		$this->setState('stage_id', $stageId > 0 ? $stageId : null);
	}

	/** @return array<string,mixed> */
	public function getMatrix(): array
	{
		$projectId = (int) $this->getState('project_id');
		if ($projectId < 1) return ['error' => 'COM_JOOMLEAGUE_RESULTMATRIX_NO_PROJECT'];
		Factory::getApplication()->bootComponent('com_joomleague');
		try {
			return (new ResultMatrixReader(Factory::getContainer()->get(DatabaseInterface::class)))->forProject(
				$projectId,
				$this->getState('stage_id'),
				Factory::getApplication()->getIdentity()->getAuthorisedViewLevels()
			);
		} catch (\Throwable) {
			return ['error' => 'COM_JOOMLEAGUE_RESULTMATRIX_UNAVAILABLE'];
		}
	}
}
