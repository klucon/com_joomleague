<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class LeaguesModel extends ListModel
{
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		$config['filter_fields'] ??= [
			'id', 'a.id',
			'name', 'a.name',
			'short_name', 'a.short_name',
			'country', 'a.country',
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
				$db->quoteName('a.middle_name'),
				$db->quoteName('a.short_name'),
				$db->quoteName('a.alias'),
				$db->quoteName('a.country'),
				$db->quoteName('a.ordering'),
				$db->quoteName('a.checked_out'),
				$db->quoteName('a.checked_out_time'),
				$db->quoteName('uc.name', 'editor'),
			])
			->from($db->quoteName('#__joomleague_league', 'a'))
			->join(
				'LEFT',
				$db->quoteName('#__users', 'uc'),
				$db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out')
			);

		$search = trim((string) $this->getState('filter.search'));

		if ($search !== '') {
			if (stripos($search, 'id:') === 0) {
				$id = (int) substr($search, 3);
				$query->where($db->quoteName('a.id') . ' = :id')
					->bind(':id', $id, ParameterType::INTEGER);
			} else {
				$search = '%' . str_replace(' ', '%', $search) . '%';
				$query->where(
					'(' . $db->quoteName('a.name') . ' LIKE :search'
					. ' OR ' . $db->quoteName('a.short_name') . ' LIKE :search'
					. ' OR ' . $db->quoteName('a.middle_name') . ' LIKE :search)'
				)->bind(':search', $search);
			}
		}

		$order = $db->escape((string) $this->getState('list.ordering', 'a.ordering'));
		$direction = $db->escape((string) $this->getState('list.direction', 'ASC'));
		$query->order($db->quoteName($order) . ' ' . $direction);

		return $query;
	}
}
