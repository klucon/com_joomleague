<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\ParticipantSummaryReader;

final class ParticipantModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$input = Factory::getApplication()->getInput();
		$this->setState('project_id', $input->getInt('project_id', 0));
		$this->setState('entry_id', $input->getInt('entry_id', 0));
	}

	/** @return array<string,mixed> */
	public function getParticipant(): array
	{
		$app = Factory::getApplication();
		$app->bootComponent('com_joomleague');
		$data = (new ParticipantSummaryReader(Factory::getContainer()->get(DatabaseInterface::class)))->read(
			(int) $this->getState('project_id'),
			(int) $this->getState('entry_id'),
			$app->getIdentity()->getAuthorisedViewLevels(),
			Factory::getDate()->format('Y-m-d')
		);

		if (isset($data['error'])) {
			$data['error'] = $data['error'] === 'participant_required'
				? 'COM_JOOMLEAGUE_PARTICIPANT_NOT_CONFIGURED'
				: 'COM_JOOMLEAGUE_PARTICIPANT_UNAVAILABLE';
		}

		return $data;
	}
}
