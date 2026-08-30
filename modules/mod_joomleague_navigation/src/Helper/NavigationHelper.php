<?php

declare(strict_types=1);

namespace Joomleague\Module\Navigation\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\ProjectNavigationReader;

final class NavigationHelper
{
	/** @return array{error?:string,project?:object,items?:list<array{key:string,label:string,url:string,icon:string}>} */
	public function getNavigation(Registry $params): array
	{
		$projectId = (int) $params->get('project_id', 0);
		if ($projectId < 1) {
			return ['error' => 'MOD_JOOMLEAGUE_NAVIGATION_NO_PROJECT'];
		}

		$app = Factory::getApplication();
		$app->bootComponent('com_joomleague');
		$language = Factory::getLanguage();
		$language->load('com_joomleague', JPATH_SITE)
			|| $language->load('com_joomleague', JPATH_SITE . '/components/com_joomleague');

		try {
			$data = (new ProjectNavigationReader(Factory::getContainer()->get(DatabaseInterface::class)))->forProject(
				$projectId,
				$app->getIdentity()->getAuthorisedViewLevels()
			);
		} catch (\Throwable) {
			return ['error' => 'MOD_JOOMLEAGUE_NAVIGATION_UNAVAILABLE'];
		}

		if (isset($data['error'])) {
			return ['error' => $data['error'] === 'project_required'
				? 'MOD_JOOMLEAGUE_NAVIGATION_NO_PROJECT'
				: 'MOD_JOOMLEAGUE_NAVIGATION_UNAVAILABLE'];
		}

		$capabilities = $data['capabilities'];
		$items = [];
		if ((bool) $params->get('show_overview', 1)) {
			$items[] = $this->item('overview', 'COM_JOOMLEAGUE_PROJECT_VIEW_TITLE', 'project', $projectId, 'icon-home');
		}
		if ($capabilities['participants']) {
			$items[] = $this->item('participants', 'COM_JOOMLEAGUE_PROJECT_NAV_PARTICIPANTS', 'participants', $projectId, 'icon-users');
		}
		if ($capabilities['personnel']) {
			$items[] = $this->item('personnel', 'COM_JOOMLEAGUE_PROJECT_NAV_PERSONNEL', 'personnel', $projectId, 'icon-address');
		}
		if ($capabilities['program']) {
			$items[] = $this->item('program', 'COM_JOOMLEAGUE_PROJECT_NAV_PROGRAM', 'results', $projectId, 'icon-calendar');
		}
		if ($capabilities['standings'] && $capabilities['program']) {
			$items[] = $this->item('standings', 'COM_JOOMLEAGUE_PROJECT_NAV_STANDINGS', 'standings', $projectId, 'icon-list');
		}
		if ((int) $capabilities['bracket_stage_id'] > 0) {
			$items[] = $this->item('bracket', 'COM_JOOMLEAGUE_PROJECT_NAV_BRACKET', 'bracket', $projectId, 'icon-tree-2', ['stage_id' => (int) $capabilities['bracket_stage_id']]);
		}
		if ($capabilities['statistics_overview']) {
			$items[] = $this->item('statistics', 'COM_JOOMLEAGUE_PROJECT_NAV_STATSOVERVIEW', 'statisticsoverview', $projectId, 'icon-chart');
		}
		if ($capabilities['result_matrix']) {
			$items[] = $this->item('matrix', 'COM_JOOMLEAGUE_PROJECT_NAV_RESULTMATRIX', 'resultmatrix', $projectId, 'icon-grid');
		}
		if ($capabilities['comparison']) {
			$items[] = $this->item('comparison', 'COM_JOOMLEAGUE_PROJECT_NAV_COMPARISON', 'comparison', $projectId, 'icon-shuffle');
		}
		if ($capabilities['progression']) {
			$items[] = $this->item('progression', 'COM_JOOMLEAGUE_PROJECT_NAV_PROGRESSION', 'standingprogression', $projectId, 'icon-trending-up');
		}

		return ['project' => $data['project'], 'items' => $items];
	}

	/** @param array<string,int|string> $extra
	 *  @return array{key:string,label:string,url:string,icon:string}
	 */
	private function item(string $key, string $label, string $view, int $projectId, string $icon, array $extra = []): array
	{
		$query = ['option' => 'com_joomleague', 'view' => $view, 'project_id' => $projectId] + $extra;

		return ['key' => $key, 'label' => $label, 'url' => Route::_('index.php?' . http_build_query($query)), 'icon' => $icon];
	}
}
