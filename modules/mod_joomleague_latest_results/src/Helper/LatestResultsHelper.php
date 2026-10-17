<?php

declare(strict_types=1);

namespace Joomleague\Module\LatestResults\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\CrossProjectProgrammeReader;
use Joomleague\Component\Joomleague\Site\Service\ProjectTemplateProvider;
use Joomleague\Module\LatestResults\Site\Configuration\LatestResultsConfiguration;

final class LatestResultsHelper
{
	/** @return array<string,mixed> */
	public function getResults(Registry $params): array
	{
		$config = LatestResultsConfiguration::fromRegistry($params);
		$app = Factory::getApplication();
		$app->bootComponent('com_joomleague');
		$language = Factory::getLanguage();
		$language->load('com_joomleague', JPATH_SITE) || $language->load('com_joomleague', JPATH_SITE . '/components/com_joomleague');
		$levels = array_values(array_unique(array_filter(array_map('intval', $app->getIdentity()?->getAuthorisedViewLevels() ?? [1]), static fn (int $id): bool => $id > 0)));
		$levels = $levels === [] ? [1] : $levels;
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$now = Factory::getDate();
		$from = clone $now;
		$from->modify('-' . $config['days_before'] . ' days')->setTime(0, 0);

		try {
			$items = (new CrossProjectProgrammeReader($db))->between(
				$levels,
				$from->toUnix(),
				$now->toUnix(),
				$config['sport_type_id'],
				$config['club_id'],
				$config['project_id'],
				$config['season_id'],
				$config['stage_id'],
				$config['round_id'],
				$config['entry_id'],
				$config['venue_id']
			);
		} catch (\Throwable $moduleFailure) { \Joomla\CMS\Log\Log::add('mod_joomleague_latest_results failure: ' . get_debug_type($moduleFailure), \Joomla\CMS\Log\Log::WARNING, 'com_joomleague.modules');
			return ['error' => 'MOD_JOOMLEAGUE_LATEST_RESULTS_UNAVAILABLE', 'config' => $config];
		}

		$items = array_values(array_filter($items, static fn (array $item): bool => (bool) $item['played']
			&& ($config['status_codes'] === [] || in_array((string) $item['status_code'], $config['status_codes'], true))));
		usort($items, static function (array $left, array $right) use ($config): int {
			$result = ($left['timestamp'] <=> $right['timestamp']) ?: ($left['id'] <=> $right['id']);
			return $config['order'] === 'newest' ? -$result : $result;
		});
		$items = array_slice($items, 0, $config['limit']);
		if ($items === []) return ['error' => 'MOD_JOOMLEAGUE_LATEST_RESULTS_EMPTY', 'config' => $config];

		$provider = new ProjectTemplateProvider($db);
		$templates = [];
		$presentationOverrides = $config['detail_override'] === '' ? [] : ['show_match_detail_button' => $config['detail_override'] === '1'];
		foreach ($items as &$item) {
			$projectId = (int) $item['project_id'];
			if (!isset($templates[$projectId])) {
				try {
					$templates[$projectId] = $provider->supports($projectId, 'results') ? $provider->resolve($projectId, 'results', $presentationOverrides) : [];
				} catch (\Throwable $moduleFailure) { \Joomla\CMS\Log\Log::add('mod_joomleague_latest_results failure: ' . get_debug_type($moduleFailure), \Joomla\CMS\Log\Log::WARNING, 'com_joomleague.modules');
					$templates[$projectId] = [];
				}
			}
			$item['show_detail'] = (bool) ($templates[$projectId]['show_match_detail_button'] ?? true);
			$item['participant_count'] = count($item['participants']);
			foreach ($item['participants'] as &$participant) $participant['image'] = \Joomleague\Component\Joomleague\Site\Service\MediaResolver::participantImage($participant);
			unset($participant);
			if ($config['participant_limit'] > 0) $item['participants'] = array_slice($item['participants'], 0, $config['participant_limit']);
		}
		unset($item);

		return ['items' => $items, 'config' => $config];
	}

}
