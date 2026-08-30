<?php

declare(strict_types=1);

namespace Joomleague\Module\NextEvent\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeScopeResolver;

final class NextEventHelper
{
	/** @return array{error?:string,item?:array<string,mixed>} */
	public function getEvent(Registry $params): array
	{
		$projectId = (int) $params->get('project_id', 0);
		if ($projectId < 1) {
			return ['error' => 'MOD_JOOMLEAGUE_NEXT_EVENT_NO_PROJECT'];
		}

		$app = Factory::getApplication();
		$app->bootComponent('com_joomleague');
		$language = Factory::getLanguage();
		$language->load('com_joomleague', JPATH_SITE)
			|| $language->load('com_joomleague', JPATH_SITE . '/components/com_joomleague');

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$viewLevels = array_values(array_unique(array_filter(
			array_map('intval', $app->getIdentity()->getAuthorisedViewLevels()),
			static fn (int $id): bool => $id > 0
		)));
		$viewLevels = $viewLevels === [] ? [1] : $viewLevels;
		$scope = (string) $params->get('scope', 'project');
		$scopeId = match ($scope) {
			'entry' => (int) $params->get('entry_id', 0),
			'club' => (int) $params->get('club_id', 0),
			default => 0,
		};
		$entryIds = (new ProgrammeScopeResolver($db))->resolve($projectId, $scope, $scopeId, $viewLevels);

		if ($scope !== 'project' && $entryIds === []) {
			return ['error' => 'MOD_JOOMLEAGUE_NEXT_EVENT_SCOPE_EMPTY'];
		}

		try {
			$items = (new ProgrammeReader($db))->forProject($projectId, $entryIds, $viewLevels);
		} catch (\Throwable) {
			return ['error' => 'MOD_JOOMLEAGUE_NEXT_EVENT_UNAVAILABLE'];
		}

		$now = Factory::getDate()->toUnix();
		foreach ($items as $item) {
			if (!$item['played'] && $item['scheduled_start'] !== null && Factory::getDate($item['scheduled_start'], 'UTC')->toUnix() >= $now) {
				return ['item' => $item];
			}
		}

		return ['error' => 'MOD_JOOMLEAGUE_NEXT_EVENT_EMPTY'];
	}
}
