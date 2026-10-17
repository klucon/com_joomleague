<?php

declare(strict_types=1);

namespace Joomleague\Module\Program\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeScopeResolver;
use Joomleague\Component\Joomleague\Site\Service\ProjectTemplateProvider;
use Joomleague\Module\Program\Site\Configuration\ProgramConfiguration;

final class ProgramHelper
{
	/** @return array{error?:string,project_name?:string,items?:list<array<string,mixed>>} */
	public function getProgramme(Registry $params): array
	{
		$config = ProgramConfiguration::fromRegistry($params);
		$projectId = $config['project_id'];
		if ($projectId < 1) {
			return ['error' => 'MOD_JOOMLEAGUE_PROGRAM_NO_PROJECT', 'config' => $config];
		}

		Factory::getApplication()->bootComponent('com_joomleague');
		$language = Factory::getLanguage();
		$language->load('com_joomleague', JPATH_SITE)
			|| $language->load('com_joomleague', JPATH_SITE . '/components/com_joomleague');

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$detailOverride = $config['detail_override'];
		$presentationOverrides = ($detailOverride === '0' || $detailOverride === '1')
			? ['show_match_detail_button' => $detailOverride === '1']
			: [];
		try {
			$provider = new ProjectTemplateProvider($db);
			$templateConfig = $provider->supports($projectId, 'results')
				? $provider->resolve($projectId, 'results', $presentationOverrides)
				: [];
		} catch (\Throwable $moduleFailure) { \Joomla\CMS\Log\Log::add('mod_joomleague_program failure: ' . get_debug_type($moduleFailure), \Joomla\CMS\Log\Log::WARNING, 'com_joomleague.modules');
			$templateConfig = [];
		}
		$viewLevels = Factory::getApplication()->getIdentity()?->getAuthorisedViewLevels() ?? [1];
		$viewLevels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $id): bool => $id > 0)));
		$viewLevels = $viewLevels === [] ? [1] : $viewLevels;
		try {
			$entryIds = (new ProgrammeScopeResolver($db))->resolve($projectId, $config['scope'], $config['scope_id'], $viewLevels);
			if ($config['scope'] !== 'project' && $entryIds === []) {
				return ['error' => 'MOD_JOOMLEAGUE_PROGRAM_SCOPE_EMPTY', 'config' => $config];
			}
			$items = (new ProgrammeReader($db))->forProject(
				$projectId,
				$entryIds,
				$viewLevels,
				$config['venue_id'] > 0 ? $config['venue_id'] : null,
				$config['stage_id'] > 0 ? $config['stage_id'] : null,
				$config['round_id'] > 0 ? $config['round_id'] : null
			);
		} catch (\Throwable $moduleFailure) { \Joomla\CMS\Log\Log::add('mod_joomleague_program failure: ' . get_debug_type($moduleFailure), \Joomla\CMS\Log\Log::WARNING, 'com_joomleague.modules');
			return ['error' => 'MOD_JOOMLEAGUE_PROGRAM_UNAVAILABLE', 'config' => $config];
		}
		foreach ($items as &$item) {
			foreach ($item['participants'] as &$participant) {
				$participant['image'] = \Joomleague\Component\Joomleague\Site\Service\MediaResolver::participantImage($participant);
			}
			unset($participant);
		}
		unset($item);

		$now = Factory::getDate()->toUnix();
		$minimum = $config['days_before'] > 0 ? $now - ($config['days_before'] * 86400) : null;
		$maximum = $config['days_after'] > 0 ? $now + ($config['days_after'] * 86400) : null;
		$items = array_values(array_filter($items, static function (array $item) use ($config, $now, $minimum, $maximum): bool {
			$timestamp = $item['scheduled_start'] === null ? null : Factory::getDate($item['scheduled_start'], 'UTC')->toUnix();
			$isFuture = $timestamp !== null && $timestamp >= $now;
			$modeMatches = match ($config['mode']) {
				'played' => $item['played'],
				'all' => true,
				default => !$item['played'] && $item['status_code'] !== 'cancelled' && $isFuture,
			};
			if (!$modeMatches || ($config['status_codes'] !== [] && !in_array((string) $item['status_code'], $config['status_codes'], true))) {
				return false;
			}
			return !($timestamp !== null && (($minimum !== null && $timestamp < $minimum) || ($maximum !== null && $timestamp > $maximum)));
		}));

		$sortFields = ['date' => 'scheduled_start', 'round' => 'round_name', 'venue' => 'venue_name', 'status' => 'status_code'];
		$sortField = $sortFields[$config['sort_by']];
		usort($items, static function (array $left, array $right) use ($sortField, $config): int {
			$leftValue = $left[$sortField] ?? null;
			$rightValue = $right[$sortField] ?? null;
			if ($leftValue === null && $rightValue !== null) return 1;
			if ($rightValue === null && $leftValue !== null) return -1;
			$result = strnatcasecmp((string) $leftValue, (string) $rightValue) ?: ($left['id'] <=> $right['id']);
			return $config['sort_direction'] === 'desc' ? -$result : $result;
		});

		if ($config['participant_limit'] > 0) {
			foreach ($items as &$item) {
				$item['participants'] = array_slice($item['participants'], 0, $config['participant_limit']);
			}
			unset($item);
		}
		$items = array_slice($items, 0, $config['limit']);

		if ($items === []) {
			return ['error' => 'MOD_JOOMLEAGUE_PROGRAM_EMPTY', 'config' => $config];
		}

		return [
			'project_name' => (string) $items[0]['project_name'],
			'items' => $items,
			'show_detail' => (bool) ($templateConfig['show_match_detail_button'] ?? true),
			'config' => $config,
		];
	}

}
