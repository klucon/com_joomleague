<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\Service\RaceRankingService;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class RaceresultsModel extends EntityListModel
{
	protected array $searchColumns = ['a.bib_number', 'p.firstname', 'p.lastname', 'pr.name', 'r.name', 'rc.name'];
	protected string $defaultOrdering = 'a.overall_place';
	protected string $defaultDirection = 'ASC';

	public function __construct($config = [], ?\Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory = null)
	{
		$config['filter_fields'] ??= ['id', 'a.id', 'overall_place', 'a.overall_place', 'bib_number', 'a.bib_number', 'duration_ms', 'a.duration_ms', 'published', 'a.published', 'ordering', 'a.ordering'];
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
				. $db->quoteName('r.name', 'round_name') . ','
				. $db->quoteName('rc.name', 'category') . ','
				. $db->quoteName('rp.sex', 'sex') . ','
				. $db->quoteName('u.name', 'editor')
			)
			->from($db->quoteName('#__joomleague_race_result', 'a'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'pr'), $db->quoteName('pr.id') . ' = ' . $db->quoteName('a.project_id'))
			->join('INNER', $db->quoteName('#__joomleague_race_participant', 'rp'), $db->quoteName('rp.id') . ' = ' . $db->quoteName('a.participant_id'))
			->join('LEFT', $db->quoteName('#__joomleague_person', 'p'), $db->quoteName('p.id') . ' = ' . $db->quoteName('a.person_id'))
			->join('LEFT', $db->quoteName('#__joomleague_round', 'r'), $db->quoteName('r.id') . ' = ' . $db->quoteName('a.round_id'))
			->join('LEFT', $db->quoteName('#__joomleague_race_category', 'rc'), $db->quoteName('rc.id') . ' = ' . $db->quoteName('a.category_id'))
			->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('a.checked_out'));
	}

	public function recalculateRankings(int $projectId = 0, int $roundId = 0): int
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select([
				$db->quoteName('rr.id'),
				$db->quoteName('rr.project_id'),
				'COALESCE(' . $db->quoteName('rr.round_id') . ', 0) AS round_id',
				$db->quoteName('rr.status'),
				$db->quoteName('rr.duration_ms'),
				'COALESCE(' . $db->quoteName('rr.category_id') . ', 0) AS category_id',
				$db->quoteName('rp.sex'),
			])
			->from($db->quoteName('#__joomleague_race_result', 'rr'))
			->join('LEFT', $db->quoteName('#__joomleague_race_participant', 'rp'), $db->quoteName('rp.id') . ' = ' . $db->quoteName('rr.participant_id'));

		if ($projectId > 0) {
			$query->where($db->quoteName('rr.project_id') . ' = :project_id')
				->bind(':project_id', $projectId, ParameterType::INTEGER);
		}

		if ($roundId > 0) {
			$query->where($db->quoteName('rr.round_id') . ' = :round_id')
				->bind(':round_id', $roundId, ParameterType::INTEGER);
		}

		$rows = array_map(static fn (object $row): array => (array) $row, $db->setQuery($query)->loadObjectList());
		$groups = [];

		foreach ($rows as $row) {
			$key = (int) $row['project_id'] . ':' . (int) $row['round_id'];
			$groups[$key][] = $row;
		}

		$ranker = new RaceRankingService();
		$updated = 0;

		foreach ($groups as $group) {
			foreach ($ranker->rank($group) as $row) {
				$id = (int) $row['id'];
				$overallPlace = (int) $row['overall_place'];
				$categoryPlace = (int) $row['category_place'];
				$sexPlace = (int) $row['sex_place'];
				$update = $db->createQuery()
					->update($db->quoteName('#__joomleague_race_result'))
					->set($db->quoteName('overall_place') . ' = :overall_place')
					->set($db->quoteName('category_place') . ' = :category_place')
					->set($db->quoteName('sex_place') . ' = :sex_place')
					->where($db->quoteName('id') . ' = :id')
					->bind(':overall_place', $overallPlace, ParameterType::INTEGER)
					->bind(':category_place', $categoryPlace, ParameterType::INTEGER)
					->bind(':sex_place', $sexPlace, ParameterType::INTEGER)
					->bind(':id', $id, ParameterType::INTEGER);

				$db->setQuery($update)->execute();
				$updated++;
			}
		}

		return $updated;
	}
}
