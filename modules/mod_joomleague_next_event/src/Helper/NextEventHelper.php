<?php

declare(strict_types=1);

namespace Joomleague\Module\NextEvent\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeScopeResolver;
use Joomleague\Module\NextEvent\Site\Configuration\NextEventConfiguration;

final class NextEventHelper
{
	/** @return array<string,mixed> */
	public function getEvent(Registry $params): array
	{
		$config = NextEventConfiguration::fromRegistry($params);
		if ($config['project_id'] < 1) return ['error' => 'MOD_JOOMLEAGUE_NEXT_EVENT_NO_PROJECT', 'config' => $config];

		$app = Factory::getApplication();
		$app->bootComponent('com_joomleague');
		$language = Factory::getLanguage();
		$language->load('com_joomleague', JPATH_SITE) || $language->load('com_joomleague', JPATH_SITE . '/components/com_joomleague');
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$viewLevels = array_values(array_unique(array_filter(array_map('intval', $app->getIdentity()?->getAuthorisedViewLevels() ?? [1]), static fn (int $id): bool => $id > 0)));
		$viewLevels = $viewLevels === [] ? [1] : $viewLevels;

		$entryIds = (new ProgrammeScopeResolver($db))->resolve($config['project_id'], $config['scope'], $config['scope_id'], $viewLevels);
		if ($config['scope'] !== 'project' && $entryIds === []) return ['error' => 'MOD_JOOMLEAGUE_NEXT_EVENT_SCOPE_EMPTY', 'config' => $config];

		try {
			$items = (new ProgrammeReader($db))->forProject(
				$config['project_id'], $entryIds, $viewLevels,
				$config['venue_id'] > 0 ? $config['venue_id'] : null,
				$config['stage_id'] > 0 ? $config['stage_id'] : null
			);
		} catch (\Throwable $moduleFailure) { \Joomla\CMS\Log\Log::add('mod_joomleague_next_event failure: ' . get_debug_type($moduleFailure), \Joomla\CMS\Log\Log::WARNING, 'com_joomleague.modules');
			return ['error' => 'MOD_JOOMLEAGUE_NEXT_EVENT_UNAVAILABLE', 'config' => $config];
		}

		if ($config['sport_type_id'] > 0) {
			$items = array_values(array_filter($items, static fn (array $item): bool => (int) $item['sport_type_id'] === $config['sport_type_id']));
		}
		if ($config['status_codes'] !== []) {
			$items = array_values(array_filter($items, static fn (array $item): bool => in_array((string) $item['status_code'], $config['status_codes'], true)));
		}

		$now = Factory::getDate()->toUnix();
		$previousFrom = $now - ($config['previous_days'] * 86400);
		$futureTo = $now + ($config['future_days'] * 86400);
		$upcoming = [];
		$previous = [];
		foreach ($items as $item) {
			if ($item['status_code'] === 'cancelled') continue;
			$timestamp = $item['scheduled_start'] === null ? null : Factory::getDate($item['scheduled_start'], 'UTC')->toUnix();
			$item['timestamp'] = $timestamp;
			if ((bool) $item['played']) {
				if ($timestamp !== null && $timestamp >= $previousFrom && $timestamp <= $now) $previous[] = $item;
			} elseif ($timestamp === null) {
				if ($config['unscheduled_mode'] === 'after_upcoming') $upcoming[] = $item;
			} elseif ($timestamp >= $now && $timestamp <= $futureTo) {
				$upcoming[] = $item;
			}
		}

		$tieDirection = $config['tie_breaker'] === 'id_desc' ? -1 : 1;
		usort($upcoming, static function (array $left, array $right) use ($tieDirection): int {
			if ($left['timestamp'] === null || $right['timestamp'] === null) return $left['timestamp'] === $right['timestamp'] ? $tieDirection * ($left['id'] <=> $right['id']) : ($left['timestamp'] === null ? 1 : -1);
			return ($left['timestamp'] <=> $right['timestamp']) ?: $tieDirection * ($left['id'] <=> $right['id']);
		});
		usort($previous, static fn (array $left, array $right): int => ($right['timestamp'] <=> $left['timestamp']) ?: $tieDirection * ($left['id'] <=> $right['id']));
		$upcoming = array_slice($upcoming, 0, $config['upcoming_limit']);
		$previous = array_slice($previous, 0, $config['previous_limit']);

		$enrich = static function (array $collection) use ($config): array {
			foreach ($collection as &$item) {
				$item['participant_count'] = count($item['participants']);
				foreach ($item['participants'] as &$participant) $participant['image'] = \Joomleague\Component\Joomleague\Site\Service\MediaResolver::participantImage($participant);
				unset($participant);
				if ($config['participant_limit'] > 0) $item['participants'] = array_slice($item['participants'], 0, $config['participant_limit']);
			}
			unset($item);
			return $collection;
		};
		$upcoming = $enrich($upcoming);
		$previous = $enrich($previous);

		return $upcoming === [] && $previous === []
			? ['error' => 'MOD_JOOMLEAGUE_NEXT_EVENT_EMPTY', 'config' => $config]
			: ['upcoming' => $upcoming, 'previous' => $previous, 'config' => $config];
	}

}
