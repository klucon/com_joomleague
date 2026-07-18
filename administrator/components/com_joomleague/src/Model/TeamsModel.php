<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\QueryInterface;

final class TeamsModel extends EntityListModel
{
	protected array $searchColumns = ['a.name', 'a.short_name', 'a.middle_name', 'a.info', 'c.name'];
	protected string $defaultOrdering = 'a.name';
	protected string $defaultDirection = 'ASC';

	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		$config['filter_fields'] ??= [
			'id',
			'a.id',
			'name',
			'a.name',
			'club',
			'c.name',
			'short_name',
			'a.short_name',
			'middle_name',
			'a.middle_name',
			'info',
			'a.info',
			'ordering',
			'a.ordering',
		];

		parent::__construct($config, $factory);
	}

	protected function buildQuery(): QueryInterface
	{
		$db = $this->getDatabase();

		return $db->createQuery()
			->select('a.*, ' . $db->quoteName('c.name', 'club') . ', ' . $db->quoteName('u.name', 'editor'))
			->from($db->quoteName('#__joomleague_team', 'a'))
			->join('LEFT', $db->quoteName('#__joomleague_club', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.club_id'))
			->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('a.checked_out'));
	}
}
