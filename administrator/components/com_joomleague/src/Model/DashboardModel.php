<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class DashboardModel extends BaseDatabaseModel
{
	public function getSections(): array
	{
		$sections = [
			['view' => 'projects', 'table' => '#__joomleague_project', 'icon' => 'icon-folder-open', 'tone' => 'blue', 'title' => 'COM_JOOMLEAGUE_MENU_PROJECTS'],
			['view' => 'sportstypes', 'table' => '#__joomleague_sports_type', 'icon' => 'icon-grid-2', 'tone' => 'violet', 'title' => 'COM_JOOMLEAGUE_MENU_SPORTS_TYPES'],
			['view' => 'leagues', 'table' => '#__joomleague_league', 'icon' => 'icon-list', 'tone' => 'cyan', 'title' => 'COM_JOOMLEAGUE_MENU_LEAGUES'],
			['view' => 'seasons', 'table' => '#__joomleague_season', 'icon' => 'icon-calendar', 'tone' => 'amber', 'title' => 'COM_JOOMLEAGUE_MENU_SEASONS'],
			['view' => 'clubs', 'table' => '#__joomleague_club', 'icon' => 'icon-home', 'tone' => 'emerald', 'title' => 'COM_JOOMLEAGUE_MENU_CLUBS'],
			['view' => 'teams', 'table' => '#__joomleague_team', 'icon' => 'icon-users', 'tone' => 'indigo', 'title' => 'COM_JOOMLEAGUE_MENU_TEAMS'],
			['view' => 'persons', 'table' => '#__joomleague_person', 'icon' => 'icon-user', 'tone' => 'rose', 'title' => 'COM_JOOMLEAGUE_MENU_PERSONS'],
			['view' => 'eventtypes', 'table' => '#__joomleague_eventtype', 'icon' => 'icon-flag', 'tone' => 'orange', 'title' => 'COM_JOOMLEAGUE_MENU_EVENT_TYPES'],
			['view' => 'statistics', 'table' => '#__joomleague_statistic', 'icon' => 'icon-chart', 'tone' => 'sky', 'title' => 'COM_JOOMLEAGUE_MENU_STATISTICS'],
			['view' => 'positions', 'table' => '#__joomleague_position', 'icon' => 'icon-address', 'tone' => 'teal', 'title' => 'COM_JOOMLEAGUE_MENU_POSITIONS'],
			['view' => 'stadiums', 'table' => '#__joomleague_playground', 'icon' => 'icon-location', 'tone' => 'lime', 'title' => 'COM_JOOMLEAGUE_MENU_STADIUMS'],
			['view' => 'tools', 'table' => null, 'icon' => 'icon-wrench', 'tone' => 'slate', 'title' => 'COM_JOOMLEAGUE_TOOLS_TITLE'],
		];

		$db = $this->getDatabase();

		foreach ($sections as &$section) {
			if ($section['table'] !== null) {
				$query = $db->createQuery()
					->select('COUNT(*)')
					->from($db->quoteName($section['table']));
				$db->setQuery($query);
				$section['count'] = (int) $db->loadResult();
			} else {
				$section['count'] = null;
			}
			unset($section['table']);
		}

		unset($section);

		return $sections;
	}

	public function getSection(string $view): ?array
	{
		foreach ($this->getSections() as $section) {
			if ($section['view'] === $view) {
				return $section;
			}
		}

		return null;
	}
}
