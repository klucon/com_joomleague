<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader;
use Joomleague\Component\Joomleague\Site\Service\PublicAccess;

/** Reads the nearest future programme event without assuming a participant count. */
final class NextmatchModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$this->setState('project_id', Factory::getApplication()->getInput()->getInt('project_id', 0));
	}

	/** @return array<string,mixed> */
	public function getEvent(): array
	{
		$projectId = (int) $this->getState('project_id');
		if ($projectId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_NEXTMATCH_NO_PROJECT'];
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		if (!PublicAccess::projectAllowed($db, $projectId)) {
			return ['error' => 'COM_JOOMLEAGUE_NEXTMATCH_UNAVAILABLE'];
		}

		Factory::getApplication()->bootComponent('com_joomleague');
		$viewLevels = Factory::getApplication()->getIdentity()->getAuthorisedViewLevels();
		$events = (new ProgrammeReader($db))->forProject($projectId, null, $viewLevels);
		$now = Factory::getDate()->toUnix();
		$item = null;

		foreach ($events as $event) {
			if (!$event['played'] && $event['scheduled_start'] !== null && Factory::getDate($event['scheduled_start'], 'UTC')->toUnix() >= $now) {
				$item = $event;
				break;
			}
		}

		if (!$item) {
			return ['error' => 'COM_JOOMLEAGUE_NEXTMATCH_EMPTY'];
		}

		return ['item' => (object) $item, 'participants' => array_map(static fn (array $participant): object => (object) [
			'slot_number' => $participant['slot'],
			'display_name' => $participant['name'],
		], $item['participants'])];
	}
}
