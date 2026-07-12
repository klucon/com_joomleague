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

final class RacecategoriesModel extends EntityListModel
{
	protected array $searchColumns = ['a.name', 'p.name'];

	public function __construct($config = [], ?\Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory = null)
	{
		$config['filter_fields'] ??= ['id', 'a.id', 'name', 'a.name', 'project', 'p.name', 'published', 'a.published', 'ordering', 'a.ordering'];
		parent::__construct($config, $factory);
	}

	protected function buildQuery(): QueryInterface
	{
		$db = $this->getDatabase();

		return $db->createQuery()
			->select('a.*,' . $db->quoteName('p.name', 'project') . ',' . $db->quoteName('u.name', 'editor'))
			->from($db->quoteName('#__joomleague_race_category', 'a'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p'), $db->quoteName('p.id') . ' = ' . $db->quoteName('a.project_id'))
			->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('a.checked_out'));
	}
}
