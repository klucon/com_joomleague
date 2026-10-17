<?php

declare(strict_types=1);

namespace Joomleague\Module\ProgrammeTicker\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\CrossProjectProgrammeReader;
use Joomleague\Component\Joomleague\Site\Service\ProjectTemplateProvider;
use Joomleague\Module\ProgrammeTicker\Site\Configuration\ProgrammeTickerConfiguration;

final class ProgrammeTickerHelper
{
	/** @return array<string,mixed> */
	public function getTicker(Registry $params): array
	{
		$config = ProgrammeTickerConfiguration::fromRegistry($params);
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
		$from->modify('-' . $config['days_before'] . ' days')->setTime(0, 0);
		$to->modify('+' . $config['days_after'] . ' days')->setTime(23, 59, 59);

		try {
			$items = (new CrossProjectProgrammeReader($db))->between(
				$viewLevels,
				$from->toUnix(),
				$to->toUnix(),
				$config['sport_type_id'], $config['club_id'], $config['project_id'], $config['season_id'],
				$config['stage_id'], $config['round_id'], $config['entry_id'], $config['venue_id']
			);
		} catch (\Throwable $moduleFailure) { \Joomla\CMS\Log\Log::add('mod_joomleague_programme_ticker failure: ' . get_debug_type($moduleFailure), \Joomla\CMS\Log\Log::WARNING, 'com_joomleague.modules');
			return ['error' => 'MOD_JOOMLEAGUE_PROGRAMME_TICKER_UNAVAILABLE', 'config' => $config];
		}
		if ($config['status_codes'] !== []) {
			$items = array_values(array_filter($items, static fn (array $item): bool => in_array((string) $item['status_code'], $config['status_codes'], true)));
		}

		$templateProvider = new ProjectTemplateProvider($db);
		$detailOverride = $config['detail_override'];
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
				} catch (\Throwable $moduleFailure) { \Joomla\CMS\Log\Log::add('mod_joomleague_programme_ticker failure: ' . get_debug_type($moduleFailure), \Joomla\CMS\Log\Log::WARNING, 'com_joomleague.modules');
					$templateByProject[$projectId] = [];
				}
			}
			$item['show_detail'] = (bool) ($templateByProject[$projectId]['show_match_detail_button'] ?? true);
			$item['participant_count'] = count($item['participants']);
			foreach ($item['participants'] as &$participant) $participant['image'] = \Joomleague\Component\Joomleague\Site\Service\MediaResolver::participantImage($participant);
			unset($participant);
			if ($config['participant_limit'] > 0) $item['participants'] = array_slice($item['participants'], 0, $config['participant_limit']);
		}
		unset($item);

		$completed = array_values(array_filter($items, static fn(array $item): bool => (bool) $item['played']));
		$upcoming = array_values(array_filter($items, static fn(array $item): bool => !(bool) $item['played'] && $item['status_code'] !== 'cancelled' && (int) $item['timestamp'] >= $now->toUnix()));
		usort($completed, static fn(array $left, array $right): int => $right['timestamp'] <=> $left['timestamp'] ?: $right['id'] <=> $left['id']);
		$completed = array_slice($completed, 0, $config['completed_limit']);
		$upcoming = array_slice($upcoming, 0, $config['upcoming_limit']);
		if ($config['content_mode'] === 'completed') $upcoming = [];
		if ($config['content_mode'] === 'upcoming') $completed = [];
		$combined = [];
		foreach ($completed as $item) { $item['_section'] = 'completed'; $combined[] = $item; }
		foreach ($upcoming as $item) { $item['_section'] = 'upcoming'; $combined[] = $item; }
		$combined = array_slice($combined, 0, $config['item_limit']);
		if ($completed === [] && $upcoming === []) {
			return ['error' => 'MOD_JOOMLEAGUE_PROGRAMME_TICKER_EMPTY', 'config' => $config];
		}

		return ['items' => $combined, 'completed' => $completed, 'upcoming' => $upcoming, 'config' => $config];
	}

}
