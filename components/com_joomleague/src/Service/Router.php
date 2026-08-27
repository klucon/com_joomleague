<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Native Joomla SEF router for all public JoomLeague views. */
final class Router extends RouterView
{
	public function __construct(
		SiteApplication $app,
		AbstractMenu $menu,
		?CategoryFactoryInterface $categoryFactory,
		private readonly DatabaseInterface $database
	)
	{
		foreach (['projects', 'clubs', 'venues', 'participants'] as $view) {
			$this->registerView(new RouterViewConfiguration($view));
		}

		foreach ([
			'project' => 'project_id', 'results' => 'project_id', 'standings' => 'project_id', 'bracket' => 'project_id',
			'team' => 'team_id', 'club' => 'club_id', 'person' => 'person_id', 'venue' => 'venue_id',
			'eventreport' => 'event_id', 'participant' => 'entry_id', 'teamplan' => 'entry_id',
		] as $view => $key) {
			$this->registerView((new RouterViewConfiguration($view))->setKey($key));
		}

		parent::__construct($app, $menu);
		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

	public function getProjectSegment($id): array { return $this->entitySegment('#__joomleague_project', (int) $id); }
	public function getProjectId($segment): int { return $this->segmentId((string) $segment); }
	public function getResultsSegment($id): array { return $this->getProjectSegment($id); }
	public function getResultsId($segment): int { return $this->getProjectId($segment); }
	public function getStandingsSegment($id): array { return $this->getProjectSegment($id); }
	public function getStandingsId($segment): int { return $this->getProjectId($segment); }
	public function getBracketSegment($id): array { return $this->getProjectSegment($id); }
	public function getBracketId($segment): int { return $this->getProjectId($segment); }
	public function getTeamSegment($id): array { return $this->entitySegment('#__joomleague_team', (int) $id); }
	public function getTeamId($segment): int { return $this->segmentId((string) $segment); }
	public function getClubSegment($id): array { return $this->entitySegment('#__joomleague_club', (int) $id); }
	public function getClubId($segment): int { return $this->segmentId((string) $segment); }
	public function getPersonSegment($id): array { return $this->entitySegment('#__joomleague_person', (int) $id); }
	public function getPersonId($segment): int { return $this->segmentId((string) $segment); }
	public function getVenueSegment($id): array { return $this->entitySegment('#__joomleague_venue', (int) $id); }
	public function getVenueId($segment): int { return $this->segmentId((string) $segment); }
	public function getEventreportSegment($id): array { return [(int) $id => (string) (int) $id]; }
	public function getEventreportId($segment): int { return $this->segmentId((string) $segment); }
	public function getParticipantSegment($id): array { return [(int) $id => (string) (int) $id]; }
	public function getParticipantId($segment): int { return $this->segmentId((string) $segment); }
	public function getTeamplanSegment($id): array { return $this->getParticipantSegment($id); }
	public function getTeamplanId($segment): int { return $this->segmentId((string) $segment); }

	private function entitySegment(string $table, int $id): array
	{
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('alias'))
			->from($this->database->quoteName($table))
			->where($this->database->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$alias = trim((string) $this->database->setQuery($query)->loadResult());

		return [$id => $alias === '' ? (string) $id : $id . '-' . $alias];
	}

	private function segmentId(string $segment): int
	{
		return max(0, (int) explode('-', $segment, 2)[0]);
	}
}
