<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\DatabaseQuery;

final class ClubsModel extends ListModel
{
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= ['name', 'country_code'];
		parent::__construct($config);
	}

	protected function populateState($ordering = 'club.name', $direction = 'ASC'): void
	{
		$input = Factory::getApplication()->getInput();
		$this->setState('filter.search', trim($input->getString('filter_search', '')));
		$this->setState('filter.country_code', $input->getCmd('filter_country_code', ''));
		parent::populateState($ordering, $direction);
	}

	protected function getListQuery(): DatabaseQuery
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$teamCount = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__joomleague_team', 'team'))
			->where('team.club_id = club.id')
			->where('team.published = 1');
		$teamCount->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'team'));
		$query = $db->getQuery(true)
			->select([
				'club.id', 'club.name', 'club.short_name', 'club.country_code', 'club.logo',
				'club.founded_date', 'club.description', '(' . $teamCount . ') AS team_count',
			])
			->from($db->quoteName('#__joomleague_club', 'club'))
			->where('club.published = 1')
			->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'club'));

		$search = (string) $this->getState('filter.search');

		if ($search !== '') {
			$search = '%' . mb_strtolower($search) . '%';
			$query->where('(LOWER(club.name) LIKE :clubSearch OR LOWER(club.short_name) LIKE :clubSearch)')
				->bind(':clubSearch', $search);
		}

		$countryCode = (string) $this->getState('filter.country_code');

		if ($countryCode !== '') {
			$query->where('club.country_code = :countryCode')
				->bind(':countryCode', $countryCode);
		}

		return $query->order('club.ordering ASC, club.name ASC, club.id ASC');
	}

	/** @return list<object> */
	public function getCountries(): array
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		return $db->setQuery(
			$db->getQuery(true)
				->select('DISTINCT country_code')
				->from($db->quoteName('#__joomleague_club', 'club'))
				->where('club.published = 1')
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'club'))
				->where('country_code IS NOT NULL')
				->where("country_code <> ''")
				->order('country_code ASC')
		)->loadObjectList() ?: [];
	}
}
