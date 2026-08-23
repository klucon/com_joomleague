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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class VenueModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$this->setState('venue_id', Factory::getApplication()->getInput()->getInt('venue_id', 0));
	}

	/** @return array<string,mixed> */
	public function getVenue(): array
	{
		$venueId = (int) $this->getState('venue_id');

		if ($venueId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_VENUE_NOT_CONFIGURED'];
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$venue = $db->setQuery(
			$db->getQuery(true)
				->select([
					'venue.id', 'venue.name', 'venue.short_name', 'venue.nickname', 'venue.address',
					'venue.postal_code', 'venue.city', 'venue.region', 'venue.country_code',
					'venue.latitude', 'venue.longitude', 'venue.timezone', 'venue.capacity',
					'venue.website', 'venue.picture', 'venue.description',
					$db->quoteName('club.id', 'club_id'), $db->quoteName('club.name', 'club_name'),
				])
				->from($db->quoteName('#__joomleague_venue', 'venue'))
				->leftJoin($db->quoteName('#__joomleague_club', 'club') . ' ON club.id = venue.owner_club_id AND club.published = 1')
				->where('venue.id = :venueId')
				->where('venue.published = 1')
				->bind(':venueId', $venueId, ParameterType::INTEGER)
		)->loadObject();

		return $venue ? ['venue' => $venue] : ['error' => 'COM_JOOMLEAGUE_VENUE_UNAVAILABLE'];
	}
}
