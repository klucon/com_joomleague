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
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class SportstypesModel extends ListModel
{
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		$config['filter_fields'] ??= [
			'id', 'a.id',
			'name', 'a.name',
			'published', 'a.published',
			'ordering', 'a.ordering',
		];

		parent::__construct($config, $factory);
	}

	protected function populateState($ordering = 'a.ordering', $direction = 'asc'): void
	{
		parent::populateState($ordering, $direction);
	}

	protected function getListQuery(): QueryInterface
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select([
				$db->quoteName('a.id'),
				$db->quoteName('a.name'),
				$db->quoteName('a.icon'),
				$db->quoteName('a.published'),
				$db->quoteName('a.ordering'),
				$db->quoteName('a.checked_out'),
				$db->quoteName('a.checked_out_time'),
				$db->quoteName('uc.name', 'editor'),
			])
			->from($db->quoteName('#__joomleague_sports_type', 'a'))
			->join(
				'LEFT',
				$db->quoteName('#__users', 'uc'),
				$db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out')
			);

		$published = $this->getState('filter.published');

		if (is_numeric($published)) {
			$published = (int) $published;
			$query->where($db->quoteName('a.published') . ' = :published')
				->bind(':published', $published, ParameterType::INTEGER);
		}

		$search = trim((string) $this->getState('filter.search'));

		if ($search !== '') {
			if (stripos($search, 'id:') === 0) {
				$id = (int) substr($search, 3);
				$query->where($db->quoteName('a.id') . ' = :id')
					->bind(':id', $id, ParameterType::INTEGER);
			} else {
				$search = '%' . str_replace(' ', '%', $search) . '%';
				$query->where($db->quoteName('a.name') . ' LIKE :search')
					->bind(':search', $search);
			}
		}

		$order = $db->escape((string) $this->getState('list.ordering', 'a.ordering'));
		$direction = $db->escape((string) $this->getState('list.direction', 'ASC'));
		$query->order($db->quoteName($order) . ' ' . $direction);

		return $query;
	}
}
