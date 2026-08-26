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

final class VenuesModel extends ListModel
{
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= ['name', 'city', 'country_code'];
		parent::__construct($config);
	}

	protected function populateState($ordering = 'venue.name', $direction = 'ASC'): void
	{
		$input = Factory::getApplication()->getInput();
		$this->setState('filter.search', trim($input->getString('filter_search', '')));
		$this->setState('filter.country_code', $input->getCmd('filter_country_code', ''));
		parent::populateState($ordering, $direction);
	}

	protected function getListQuery(): DatabaseQuery
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select([
				'venue.id', 'venue.name', 'venue.short_name', 'venue.city', 'venue.region',
				'venue.country_code', 'venue.capacity', 'venue.picture',
				$db->quoteName('club.id', 'club_id'), $db->quoteName('club.name', 'club_name'),
			])
			->from($db->quoteName('#__joomleague_venue', 'venue'))
			->leftJoin($db->quoteName('#__joomleague_club', 'club') . ' ON club.id = venue.owner_club_id AND club.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'club'))
			->where('venue.published = 1')
			->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'venue'));

		$search = (string) $this->getState('filter.search');

		if ($search !== '') {
			$search = '%' . mb_strtolower($search) . '%';
			$query->where('(LOWER(venue.name) LIKE :venueSearch OR LOWER(venue.short_name) LIKE :venueSearch OR LOWER(venue.city) LIKE :venueSearch)')
				->bind(':venueSearch', $search);
		}

		$countryCode = (string) $this->getState('filter.country_code');

		if ($countryCode !== '') {
			$query->where('venue.country_code = :countryCode')->bind(':countryCode', $countryCode);
		}

		return $query->order('venue.ordering ASC, venue.name ASC, venue.id ASC');
	}

	/** @return list<object> */
	public function getCountries(): array
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		return $db->setQuery(
			$db->getQuery(true)
				->select('DISTINCT country_code')
				->from($db->quoteName('#__joomleague_venue', 'venue'))
				->where('venue.published = 1')
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'venue'))
				->where('country_code IS NOT NULL')
				->where("country_code <> ''")
				->order('country_code ASC')
		)->loadObjectList() ?: [];
	}
}
