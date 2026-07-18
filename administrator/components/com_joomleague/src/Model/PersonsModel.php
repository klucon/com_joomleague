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
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class PersonsModel extends EntityListModel
{
	protected array $searchColumns = ['a.firstname', 'a.lastname', 'a.nickname', 'a.knvbnr'];
	protected string $defaultOrdering = 'a.lastname';
	protected string $defaultDirection = 'ASC';

	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		$config['filter_fields'] ??= [
			'id', 'a.id',
			'lastname', 'a.lastname',
			'firstname', 'a.firstname',
			'knvbnr', 'a.knvbnr',
			'country', 'a.country',
			'published', 'a.published',
			'ordering', 'a.ordering',
		];

		parent::__construct($config, $factory);
	}

	protected function buildQuery(): QueryInterface
	{
		$db = $this->getDatabase();

		return $db->createQuery()
			->select(
				'a.*, CONCAT_WS(" ", NULLIF(a.lastname, ""), NULLIF(a.firstname, "")) AS fullname, '
				. $db->quoteName('p.name', 'position') . ', '
				. $db->quoteName('u.name', 'editor')
			)
			->from($db->quoteName('#__joomleague_person', 'a'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'p'), $db->quoteName('p.id') . '=' . $db->quoteName('a.position_id'))
			->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . '=' . $db->quoteName('a.checked_out'));
	}

	protected function getListQuery(): QueryInterface
	{
		$db = $this->getDatabase();
		$query = $this->buildQuery();
		$search = trim((string) $this->getState('filter.search'));

		if ($search !== '') {
			if (str_starts_with(strtolower($search), 'id:')) {
				$id = (int) substr($search, 3);
				$query->where($db->quoteName('a.id') . ' = :id')
					->bind(':id', $id, ParameterType::INTEGER);
			} else {
				$search = '%' . str_replace(' ', '%', $search) . '%';
				$parts = array_map(fn ($column) => $db->quoteName($column) . ' LIKE :search', $this->searchColumns);
				$query->where('(' . implode(' OR ', $parts) . ')')
					->bind(':search', $search);
			}
		}

		$state = $this->getState('filter.published');

		if ($state !== '' && $state !== null) {
			$state = (int) $state;
			$query->where($db->quoteName('a.published') . ' = :state')
				->bind(':state', $state, ParameterType::INTEGER);
		}

		$ordering = (string) $this->getState('list.ordering', $this->defaultOrdering);
		$direction = strtoupper((string) $this->getState('list.direction', $this->defaultDirection));
		$direction = \in_array($direction, ['ASC', 'DESC'], true) ? $direction : $this->defaultDirection;

		if (\in_array($ordering, ['a.lastname', 'a.firstname'], true)) {
			$query->order($ordering . ' COLLATE utf8mb4_czech_ci ' . $db->escape($direction));

			if ($ordering === 'a.lastname') {
				$query->order('a.firstname COLLATE utf8mb4_czech_ci ' . $db->escape($direction));
			}

			$query->order($db->quoteName('a.id') . ' ASC');

			return $query;
		}

		$query->order($db->quoteName($ordering) . ' ' . $db->escape($direction));

		return $query;
	}
}
