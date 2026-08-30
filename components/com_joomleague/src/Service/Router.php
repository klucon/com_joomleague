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
use Joomla\CMS\Filter\OutputFilter;
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
		foreach (['projects', 'clubs', 'venues', 'participants', 'about'] as $view) {
			$this->registerView(new RouterViewConfiguration($view));
		}

		foreach ([
			'project' => 'project_id', 'results' => 'project_id', 'standings' => 'project_id', 'bracket' => 'project_id',
			'statranking' => 'project_id',
			'eventranking' => 'project_id', 'resultmatrix' => 'project_id',
			'personnel' => 'project_id',
			'participantstats' => 'entry_id',
			'comparison' => 'project_id',
			'standingprogression' => 'project_id',
			'statisticsoverview' => 'project_id',
			'team' => 'team_id', 'club' => 'club_id', 'person' => 'person_id', 'venue' => 'venue_id',
			'eventreport' => 'event_id', 'programitem' => 'match_id', 'participant' => 'entry_id', 'teamplan' => 'entry_id', 'clubplan' => 'club_id', 'nextmatch' => 'project_id',
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
	public function getStatrankingSegment($id): array { return $this->getProjectSegment($id); }
	public function getStatrankingId($segment): int { return $this->getProjectId($segment); }
	public function getEventrankingSegment($id): array { return $this->getProjectSegment($id); }
	public function getEventrankingId($segment): int { return $this->getProjectId($segment); }
	public function getResultmatrixSegment($id): array { return $this->getProjectSegment($id); }
	public function getResultmatrixId($segment): int { return $this->getProjectId($segment); }
	public function getPersonnelSegment($id): array { return $this->getProjectSegment($id); }
	public function getPersonnelId($segment): int { return $this->getProjectId($segment); }
	public function getParticipantstatsSegment($id): array { return $this->entrySegment((int) $id); }
	public function getParticipantstatsId($segment): int { return $this->segmentId((string) $segment); }
	public function getComparisonSegment($id): array { return $this->getProjectSegment($id); }
	public function getComparisonId($segment): int { return $this->getProjectId($segment); }
	public function getStandingprogressionSegment($id): array { return $this->getProjectSegment($id); }
	public function getStandingprogressionId($segment): int { return $this->getProjectId($segment); }
	public function getStatisticsoverviewSegment($id): array { return $this->getProjectSegment($id); }
	public function getStatisticsoverviewId($segment): int { return $this->getProjectId($segment); }
	public function getTeamSegment($id): array { return $this->entitySegment('#__joomleague_team', (int) $id); }
	public function getTeamId($segment): int { return $this->segmentId((string) $segment); }
	public function getClubSegment($id): array { return $this->entitySegment('#__joomleague_club', (int) $id); }
	public function getClubId($segment): int { return $this->segmentId((string) $segment); }
	public function getPersonSegment($id): array { return $this->entitySegment('#__joomleague_person', (int) $id); }
	public function getPersonId($segment): int { return $this->segmentId((string) $segment); }
	public function getVenueSegment($id): array { return $this->entitySegment('#__joomleague_venue', (int) $id); }
	public function getVenueId($segment): int { return $this->segmentId((string) $segment); }
	public function getProgramitemSegment($id): array { return [(int) $id => (string) (int) $id]; }
	public function getProgramitemId($segment): int { return $this->segmentId((string) $segment); }
	public function getEventreportSegment($id): array
	{
		$eventId = (int) $id;
		$query = $this->database->getQuery(true)
			->select([
				$this->database->quoteName('event.match_number'),
				$this->database->quoteName('round.name', 'round_name'),
			])
			->from($this->database->quoteName('#__joomleague_project_match', 'event'))
			->leftJoin($this->database->quoteName('#__joomleague_project_round', 'round') . ' ON round.id = event.round_id')
			->where($this->database->quoteName('event.id') . ' = :eventId')
			->bind(':eventId', $eventId, ParameterType::INTEGER);
		$row = $this->database->setQuery($query)->loadObject();
		$label = $row === null ? '' : ((string) $row->match_number ?: (string) $row->round_name);
		$slug = OutputFilter::stringURLSafe($label);

		return [$eventId => $slug === '' ? (string) $eventId : $eventId . '-' . $slug];
	}
	public function getEventreportId($segment): int { return $this->segmentId((string) $segment); }
	public function getParticipantSegment($id): array { return $this->entrySegment((int) $id); }
	public function getParticipantId($segment): int { return $this->segmentId((string) $segment); }
	public function getTeamplanSegment($id): array { return $this->getParticipantSegment($id); }
	public function getTeamplanId($segment): int { return $this->segmentId((string) $segment); }
	public function getClubplanSegment($id): array { return $this->getClubSegment($id); }
	public function getClubplanId($segment): int { return $this->segmentId((string) $segment); }
	public function getNextmatchSegment($id): array { return $this->getProjectSegment($id); }
	public function getNextmatchId($segment): int { return $this->segmentId((string) $segment); }

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

	private function entrySegment(int $id): array
	{
		$query = $this->database->getQuery(true)
			->select("COALESCE(NULLIF(entry.display_name, ''), team.alias, team.name, NULLIF(TRIM(CONCAT(person.first_name, ' ', person.last_name)), ''), CONCAT('entry-', entry.id))")
			->from($this->database->quoteName('#__joomleague_project_entry', 'entry'))
			->leftJoin($this->database->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
			->leftJoin($this->database->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
			->where('entry.id = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$alias = OutputFilter::stringURLSafe((string) $this->database->setQuery($query)->loadResult());

		return [$id => $alias === '' ? (string) $id : $id . '-' . $alias];
	}

	private function segmentId(string $segment): int
	{
		return max(0, (int) explode('-', $segment, 2)[0]);
	}
}
