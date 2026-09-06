<?php

declare(strict_types=1);

namespace Joomleague\Module\Calendar\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\CrossProjectProgrammeReader;
use Joomleague\Component\Joomleague\Site\Service\ProjectTemplateProvider;

final class CalendarHelper
{
	/** @return array{error?:string,groups?:list<array{date:string,items:list<array<string,mixed>>}>} */
	public function getCalendar(Registry $params): array
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

		$now = Factory::getDate();
		$from = clone $now;
		$to = clone $now;
		$from->modify('-' . min(365, max(0, (int) $params->get('days_before', 7))) . ' days')->setTime(0, 0);
		$to->modify('+' . min(5000, max(1, (int) $params->get('days_after', 30))) . ' days')->setTime(23, 59, 59);
		$fromUnix = $from->toUnix();
		$toUnix = $to->toUnix();
		$templateProvider = new ProjectTemplateProvider($db);
		$detailOverride = (string) $params->get('template_show_match_detail_button', '');
		$presentationOverrides = in_array($detailOverride, ['0', '1'], true)
			? ['show_match_detail_button' => $detailOverride === '1']
			: [];
		try {
			$items = (new CrossProjectProgrammeReader($db))->between($viewLevels, $fromUnix, $toUnix, $sportTypeId, $clubId);
		} catch (\Throwable) {
			return ['error' => 'MOD_JOOMLEAGUE_CALENDAR_EMPTY'];
		}
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
		$items = array_slice($items, 0, min(200, max(1, (int) $params->get('limit', 30))));
		if ($items === []) {
			return ['error' => 'MOD_JOOMLEAGUE_CALENDAR_EMPTY'];
		}

		$groups = [];
		foreach ($items as $item) {
			$date = Factory::getDate((string) $item['scheduled_start'], 'UTC')->format('Y-m-d', true);
			$groups[$date] ??= ['date' => $date, 'items' => []];
			$groups[$date]['items'][] = $item;
		}

		return ['groups' => array_values($groups)];
	}
}
