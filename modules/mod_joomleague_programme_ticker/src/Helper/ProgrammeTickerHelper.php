<?php

declare(strict_types=1);

namespace Joomleague\Module\ProgrammeTicker\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\CrossProjectProgrammeReader;
use Joomleague\Component\Joomleague\Site\Service\ProjectTemplateProvider;

final class ProgrammeTickerHelper
{
	/** @return array{error?:string,completed?:list<array<string,mixed>>,upcoming?:list<array<string,mixed>>} */
	public function getTicker(Registry $params): array
	{
		Factory::getApplication()->bootComponent('com_joomleague');
		Factory::getLanguage()->load('com_joomleague', JPATH_SITE)
			|| Factory::getLanguage()->load('com_joomleague', JPATH_SITE . '/components/com_joomleague');
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$viewLevels = Factory::getApplication()->getIdentity()?->getAuthorisedViewLevels() ?? [1];
		$viewLevels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn(int $id): bool => $id > 0)));
		$viewLevels = $viewLevels === [] ? [1] : $viewLevels;
		$now = Factory::getDate();
		$from = clone $now;
		$to = clone $now;
		$from->modify('-' . min(5000, max(0, (int) $params->get('days_before', 30))) . ' days')->setTime(0, 0);
		$to->modify('+' . min(5000, max(1, (int) $params->get('days_after', 30))) . ' days')->setTime(23, 59, 59);

		try {
			$items = (new CrossProjectProgrammeReader($db))->between(
				$viewLevels,
				$from->toUnix(),
				$to->toUnix(),
				max(0, (int) $params->get('sport_type_id', 0)),
				max(0, (int) $params->get('club_id', 0))
			);
		} catch (\Throwable) {
			return ['error' => 'MOD_JOOMLEAGUE_PROGRAMME_TICKER_EMPTY'];
		}

		$templateProvider = new ProjectTemplateProvider($db);
		$detailOverride = (string) $params->get('template_show_match_detail_button', '');
		$presentationOverrides = in_array($detailOverride, ['0', '1'], true)
			? ['show_match_detail_button' => $detailOverride === '1']
			: [];
		$templateByProject = [];
		foreach ($items as &$item) {
			$projectId = (int) $item['project_id'];
			if (!array_key_exists($projectId, $templateByProject)) {
				try {
					$templateByProject[$projectId] = $templateProvider->supports($projectId, 'results')
						? $templateProvider->resolve($projectId, 'results', $presentationOverrides)
						: [];
				} catch (\Throwable) {
					$templateByProject[$projectId] = [];
				}
			}
			$item['show_detail'] = (bool) ($templateByProject[$projectId]['show_match_detail_button'] ?? true);
		}
		unset($item);

		$completed = array_values(array_filter($items, static fn(array $item): bool => (bool) $item['played']));
		$upcoming = array_values(array_filter($items, static fn(array $item): bool => !(bool) $item['played'] && (int) $item['timestamp'] >= $now->toUnix()));
		usort($completed, static fn(array $left, array $right): int => $right['timestamp'] <=> $left['timestamp'] ?: $right['id'] <=> $left['id']);
		$completed = array_slice($completed, 0, min(20, max(0, (int) $params->get('completed_limit', 3))));
		$upcoming = array_slice($upcoming, 0, min(20, max(0, (int) $params->get('upcoming_limit', 5))));
		if ($completed === [] && $upcoming === []) {
			return ['error' => 'MOD_JOOMLEAGUE_PROGRAMME_TICKER_EMPTY'];
		}

		return ['completed' => $completed, 'upcoming' => $upcoming];
	}
}
