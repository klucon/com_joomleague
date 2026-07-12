<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class RoundsModel extends EntityListModel
{
	protected array $searchColumns = ['a.name'];
	private AdministratorApplication $application;

	public function setApplication(AdministratorApplication $a): void
	{
		$this->application = $a;
	}

	public function __construct($c = [], $f = null)
	{
		$c['filter_fields'] = ['id', 'a.id', 'roundcode', 'a.roundcode', 'name', 'a.name', 'published', 'a.published', 'round_date_first', 'a.round_date_first', 'ordering', 'a.ordering'];
		parent::__construct($c, $f);
	}

	protected function populateState($o = 'a.round_date_first', $d = 'asc'): void
	{
		$input = $this->application->getInput();
		$id = $input->getInt('project_id');

		if (!$id) {
			$pid = $input->get('pid', [], 'array');
			$id = (int) ($pid[0] ?? 0);
		}

		if ($id) {
			$this->application->setUserState('com_joomleague.rounds.project_id', $id);
		}

		$this->setState('filter.project_id', $id ?: $this->application->getUserState('com_joomleague.rounds.project_id'));
		parent::populateState($o, $d);
	}

	public function getProjectContext(): ?object
	{
		$id = (int) $this->getState('filter.project_id');

		if ($id < 1) {
			return null;
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select([
				'p.id',
				'p.name',
				'p.timezone',
				'l.name AS league',
				's.name AS season',
				'st.name AS sport',
				'(SELECT COUNT(*) FROM #__joomleague_round r WHERE r.project_id = p.id) AS round_count',
				'(SELECT COUNT(*) FROM #__joomleague_match m JOIN #__joomleague_round r2 ON r2.id = m.round_id WHERE r2.project_id = p.id) AS match_count',
			])
			->from('#__joomleague_project p')
			->join('LEFT', '#__joomleague_league l ON l.id = p.league_id')
			->join('LEFT', '#__joomleague_season s ON s.id = p.season_id')
			->join('LEFT', '#__joomleague_sports_type st ON st.id = p.sports_type_id')
			->where('p.id = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObject();
	}

	protected function buildQuery(): QueryInterface
	{
		$db = $this->getDatabase();
		$id = (int) $this->getState('filter.project_id');

		return $db->createQuery()
			->select('a.*,u.name AS editor,(SELECT COUNT(*) FROM #__joomleague_match m WHERE m.round_id=a.id) AS match_count,(SELECT COUNT(*) FROM #__joomleague_match m2 WHERE m2.round_id=a.id AND m2.team1_result IS NOT NULL AND m2.team2_result IS NOT NULL) AS result_count')
			->from('#__joomleague_round a')
			->join('LEFT', '#__users u ON u.id=a.checked_out')
			->where('a.project_id=' . (int) $id);
	}
}
