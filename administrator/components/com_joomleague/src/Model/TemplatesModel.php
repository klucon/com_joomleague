<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class TemplatesModel extends EntityListModel
{
	protected array $searchColumns = ['a.template', 'a.func', 'a.title'];
	private AdministratorApplication $application;

	public function __construct($config = [], $factory = null)
	{
		$config['filter_fields'] = ['id', 'a.id', 'template', 'a.template', 'func', 'a.func', 'title', 'a.title', 'published', 'a.published'];
		parent::__construct($config, $factory);
	}

	public function setApplication(AdministratorApplication $application): void
	{
		$this->application = $application;
	}

	protected function populateState($ordering = 'a.template', $direction = 'ASC'): void
	{
		$input = $this->application->getInput();
		$projectId = $input->getInt('project_id');

		if ($projectId > 0) {
			$this->application->setUserState('com_joomleague.templates.project_id', $projectId);
		}

		$this->setState('filter.project_id', $projectId ?: (int) $this->application->getUserState('com_joomleague.templates.project_id'));
		parent::populateState($ordering, $direction);
	}

	public function getProjectContext(): ?object
	{
		$projectId = (int) $this->getState('filter.project_id');

		if ($projectId < 1) {
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
			->where('p.id = :project_id')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObject();
	}

	protected function buildQuery(): QueryInterface
	{
		$db = $this->getDatabase();
		$projectId = (int) $this->getState('filter.project_id');

		$query = $db->createQuery()
			->select('a.*, u.name AS editor')
			->from('#__joomleague_template_config a')
			->join('LEFT', '#__users u ON u.id = a.checked_out');

		if ($projectId > 0) {
			$query->where('a.project_id = :project_id')->bind(':project_id', $projectId, ParameterType::INTEGER);
		}

		return $query;
	}
}
