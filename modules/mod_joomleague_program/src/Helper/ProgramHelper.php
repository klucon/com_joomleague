<?php

declare(strict_types=1);

namespace Joomleague\Module\Program\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeScopeResolver;

final class ProgramHelper
{
	/** @return array{error?:string,project_name?:string,items?:list<array<string,mixed>>} */
	public function getProgramme(Registry $params): array
	{
		$projectId = (int) $params->get('project_id', 0);
		if ($projectId < 1) {
			return ['error' => 'MOD_JOOMLEAGUE_PROGRAM_NO_PROJECT'];
		}

		Factory::getApplication()->bootComponent('com_joomleague');
		$language = Factory::getLanguage();
		$language->load('com_joomleague', JPATH_SITE)
			|| $language->load('com_joomleague', JPATH_SITE . '/components/com_joomleague');

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$viewLevels = Factory::getApplication()->getIdentity()->getAuthorisedViewLevels();
		$viewLevels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $id): bool => $id > 0)));
		$viewLevels = $viewLevels === [] ? [1] : $viewLevels;
		$scope = (string) $params->get('scope', 'project');
		$scopeId = match ($scope) {
			'entry' => (int) $params->get('entry_id', 0),
			'club' => (int) $params->get('club_id', 0),
			default => 0,
		};
		$entryIds = (new ProgrammeScopeResolver($db))->resolve($projectId, $scope, $scopeId, $viewLevels);

		if ($scope !== 'project' && $entryIds === []) {
			return ['error' => 'MOD_JOOMLEAGUE_PROGRAM_SCOPE_EMPTY'];
		}

		try {
			$items = (new ProgrammeReader($db))->forProject($projectId, $entryIds, $viewLevels);
		} catch (\Throwable) {
			return ['error' => 'MOD_JOOMLEAGUE_PROGRAM_UNAVAILABLE'];
		}

		$mode = (string) $params->get('mode', 'upcoming');
		$now = Factory::getDate()->toUnix();
		$items = array_values(array_filter($items, static function (array $item) use ($mode, $now): bool {
			$isFuture = $item['scheduled_start'] !== null && Factory::getDate($item['scheduled_start'], 'UTC')->toUnix() >= $now;
			return match ($mode) {
				'played' => $item['played'],
				'all' => true,
				default => !$item['played'] && $isFuture,
			};
		}));

		if ($mode === 'played') {
			$items = array_reverse($items);
		}

		$limit = min(50, max(1, (int) $params->get('limit', 5)));
		$items = array_slice($items, 0, $limit);

		if ($items === []) {
			return ['error' => 'MOD_JOOMLEAGUE_PROGRAM_EMPTY'];
		}

		return ['project_name' => (string) $items[0]['project_name'], 'items' => $items];
	}
}
