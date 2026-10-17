<?php

declare(strict_types=1);

namespace Joomleague\Module\Calendar\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\CrossProjectProgrammeReader;
use Joomleague\Component\Joomleague\Site\Service\ProjectTemplateProvider;

final class CalendarHelper
{
	/** @return array{error?:string,groups?:list<array{date:string,items:list<array<string,mixed>>}>} */
	public function getCalendar(Registry $params, int $moduleId = 0): array
	{
		Factory::getApplication()->bootComponent('com_joomleague');
		$language = Factory::getLanguage();
		$language->load('com_joomleague', JPATH_SITE)
			|| $language->load('com_joomleague', JPATH_SITE . '/components/com_joomleague');

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$viewLevels = Factory::getApplication()->getIdentity()?->getAuthorisedViewLevels() ?? [1];
		$viewLevels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn(int $id): bool => $id > 0)));
		$viewLevels = $viewLevels === [] ? [1] : $viewLevels;
		$sportTypeId = max(0, (int) $params->get('sport_type_id', 0));
		$clubId = max(0, (int) $params->get('club_id', 0));

		$timezone = CalendarOptions::timezone((string) $params->get('timezone', ''), (string) Factory::getApplication()->get('offset', 'UTC'));
		$now = new \DateTimeImmutable('now', $timezone);
		[$fromUnix, $toUnix] = CalendarOptions::window($now, (int) $params->get('days_before', 7), (int) $params->get('days_after', 30));
		$presentation = (string) $params->get('presentation', 'agenda');
		$presentation = in_array($presentation, ['list', 'agenda', 'month'], true) ? $presentation : 'agenda';
		$meta = ['timezone' => $timezone->getName(), 'module_id' => max(0, $moduleId), 'presentation' => $presentation];
		if ($presentation === 'month') {
			$key = 'jl_calendar_' . max(0, $moduleId) . '_month';
			$value = Factory::getApplication()->getInput()->getString($key, substr((string) $params->get('initial_date', ''), 0, 7));
			$month = CalendarOptions::month($value, $now);
			[$fromUnix, $toUnix] = CalendarOptions::monthWindow($month);
			$meta['month'] = $month->format('Y-m-d');
			$meta['grid'] = CalendarOptions::monthGrid($month, (int) $params->get('first_weekday', 1));
			foreach (['previous' => '-1 month', 'next' => '+1 month', 'today' => ''] as $name => $change) {
				$url = clone Uri::getInstance();
				$url->setVar($key, $change === '' ? $now->format('Y-m') : $month->modify($change)->format('Y-m'));
				$meta[$name . '_url'] = $url->toString(['path', 'query']);
			}
		}
		$templateProvider = new ProjectTemplateProvider($db);
		$detailOverride = (string) $params->get('template_show_match_detail_button', '');
		$presentationOverrides = in_array($detailOverride, ['0', '1'], true)
			? ['show_match_detail_button' => $detailOverride === '1']
			: [];
		try {
			$items = (new CrossProjectProgrammeReader($db))->between(
				$viewLevels, $fromUnix, $toUnix, $sportTypeId, $clubId,
				max(0, (int) $params->get('project_id', 0)),
				max(0, (int) $params->get('season_id', 0)),
				max(0, (int) $params->get('stage_id', 0)),
				0, max(0, (int) $params->get('entry_id', 0)),
				max(0, (int) $params->get('venue_id', 0))
			);
		} catch (\Throwable $moduleFailure) { \Joomla\CMS\Log\Log::add('mod_joomleague_calendar failure: ' . get_debug_type($moduleFailure), \Joomla\CMS\Log\Log::WARNING, 'com_joomleague.modules');
			return $meta + ['error' => 'MOD_JOOMLEAGUE_CALENDAR_EMPTY'];
		}
		$items = CalendarOptions::select($items, (string) $params->get('mode', 'all'), (string) $params->get('direction', 'asc'), (int) $params->get('offset', 0), (int) $params->get('limit', 30), $now->getTimestamp());
		$templateByProject = [];
		foreach ($items as &$item) {
			foreach ($item['participants'] as &$participant) {
				$participant['image'] = \Joomleague\Component\Joomleague\Site\Service\MediaResolver::participantImage($participant);
			}
			unset($participant);
			$projectId = (int) $item['project_id'];
			if (!array_key_exists($projectId, $templateByProject)) {
				try {
					$templateByProject[$projectId] = $templateProvider->supports($projectId, 'results')
						? $templateProvider->resolve($projectId, 'results', $presentationOverrides)
						: [];
				} catch (\Throwable $moduleFailure) { \Joomla\CMS\Log\Log::add('mod_joomleague_calendar failure: ' . get_debug_type($moduleFailure), \Joomla\CMS\Log\Log::WARNING, 'com_joomleague.modules');
					$templateByProject[$projectId] = [];
				}
			}
			$item['show_detail'] = (bool) ($presentationOverrides['show_match_detail_button']
				?? $templateByProject[$projectId]['show_match_detail_button'] ?? true);
		}
		unset($item);
		if ($items === []) {
			return $meta + ['error' => 'MOD_JOOMLEAGUE_CALENDAR_EMPTY'];
		}

		$groups = [];
		foreach ($items as $item) {
			$date = (new \DateTimeImmutable('@' . $item['timestamp']))->setTimezone($timezone)->format('Y-m-d');
			$groups[$date] ??= ['date' => $date, 'items' => []];
			$groups[$date]['items'][] = $item;
		}

		return $meta + ['groups' => array_values($groups)];
	}
}
