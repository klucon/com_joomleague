<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\ParticipantStatisticReader;

final class ParticipantstatsModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$input = Factory::getApplication()->getInput();
		$this->setState('project_id', $input->getInt('project_id', 0));
		$this->setState('entry_id', $input->getInt('entry_id', 0));
	}

	/** @return array<string,mixed> */
	public function getStatistics(): array
	{
		$projectId = (int) $this->getState('project_id'); $entryId = (int) $this->getState('entry_id');
		if ($projectId < 1 || $entryId < 1) return ['error' => 'COM_JOOMLEAGUE_PARTICIPANTSTATS_NOT_CONFIGURED'];
		Factory::getApplication()->bootComponent('com_joomleague');
		try {
			return (new ParticipantStatisticReader(Factory::getContainer()->get(DatabaseInterface::class)))->forEntry($projectId, $entryId, Factory::getApplication()->getIdentity()->getAuthorisedViewLevels());
		} catch (\Throwable) {
			return ['error' => 'COM_JOOMLEAGUE_PARTICIPANTSTATS_UNAVAILABLE'];
		}
	}
}
