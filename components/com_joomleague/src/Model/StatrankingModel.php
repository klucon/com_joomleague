<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\StatisticRankingReader;

final class StatrankingModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$input = Factory::getApplication()->getInput();
		$this->setState('project_id', $input->getInt('project_id', 0));
		$this->setState('statistic_code', $input->getCmd('statistic_code', ''));
	}

	/** @return array<string,mixed> */
	public function getRanking(): array
	{
		$projectId = (int) $this->getState('project_id');
		if ($projectId < 1) return ['error' => 'COM_JOOMLEAGUE_STATRANKING_NO_PROJECT'];
		Factory::getApplication()->bootComponent('com_joomleague');
		try {
			$levels = Factory::getApplication()->getIdentity()->getAuthorisedViewLevels();
			$code = trim((string) $this->getState('statistic_code')) ?: null;
			return (new StatisticRankingReader(Factory::getContainer()->get(DatabaseInterface::class)))->forProject($projectId, $code, 100, $levels);
		} catch (\Throwable) {
			return ['error' => 'COM_JOOMLEAGUE_STATRANKING_UNAVAILABLE'];
		}
	}
}
