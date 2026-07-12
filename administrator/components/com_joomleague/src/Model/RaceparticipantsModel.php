<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\Database\QueryInterface;

final class RaceparticipantsModel extends EntityListModel
{
	protected array $searchColumns = ['a.bib_number', 'p.firstname', 'p.lastname', 'pr.name', 'c.name', 't.name'];

	public function __construct($config = [], ?\Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory = null)
	{
		$config['filter_fields'] ??= ['id', 'a.id', 'bib_number', 'a.bib_number', 'runner', 'project', 'pr.name', 'published', 'a.published', 'ordering', 'a.ordering'];
		parent::__construct($config, $factory);
	}

	protected function buildQuery(): QueryInterface
	{
		$db = $this->getDatabase();

		return $db->createQuery()
			->select(
				'a.*,'
				. 'TRIM(CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . ')) AS runner,'
				. $db->quoteName('pr.name', 'project') . ','
				. $db->quoteName('rc.name', 'category') . ','
				. $db->quoteName('c.name', 'club') . ','
				. $db->quoteName('t.name', 'team') . ','
				. $db->quoteName('u.name', 'editor')
			)
			->from($db->quoteName('#__joomleague_race_participant', 'a'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'pr'), $db->quoteName('pr.id') . ' = ' . $db->quoteName('a.project_id'))
			->join('INNER', $db->quoteName('#__joomleague_person', 'p'), $db->quoteName('p.id') . ' = ' . $db->quoteName('a.person_id'))
			->join('LEFT', $db->quoteName('#__joomleague_race_category', 'rc'), $db->quoteName('rc.id') . ' = ' . $db->quoteName('a.category_id'))
			->join('LEFT', $db->quoteName('#__joomleague_club', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.club_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 't'), $db->quoteName('t.id') . ' = ' . $db->quoteName('a.team_id'))
			->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('a.checked_out'));
	}
}
