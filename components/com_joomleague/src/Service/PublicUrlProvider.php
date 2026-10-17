<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\User\User;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Menu\SiteMenu;
use Joomla\CMS\Router\SiteRouter;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Read-only discovery for sitemap adapters running in the Joomla site context. */
final class PublicUrlProvider
{
	private const TYPES = [
		'project' => ['project', 'project_id'],
		'club' => ['club', 'club_id'],
		'team' => ['team', 'team_id'],
		'person' => ['person', 'person_id'],
		'venue' => ['venue', 'venue_id'],
		'eventreport' => ['project_match', 'event_id'],
	];

	public function __construct(private readonly DatabaseInterface $database) {}

	/** @return list<string> */
	public function types(): array
	{
		return array_keys(self::TYPES);
	}

	/**
	 * Cursor is the last returned ID, not an offset. URLs use the active site language.
	 * @return list<array{id:int,url:string,lastmod:null}>
	 */
	public function read(string $type, int $afterId = 0, int $limit = 500): array
	{
		if (!isset(self::TYPES[$type]) || $afterId < 0 || $limit < 1 || $limit > 1000) {
			throw new \InvalidArgumentException('Invalid public URL discovery batch.');
		}

		[$table, $parameter] = self::TYPES[$type];
		$db = $this->database;
		$levels = array_values(array_unique(array_filter(
			array_map('intval', (new User())->getAuthorisedViewLevels()),
			static fn (int $id): bool => $id > 0
		)));
		if ($levels === []) {
			return [];
		}
		$access = implode(',', $levels);
		$query = $db->getQuery(true)->select($db->quoteName('item.id'))
			->from($db->quoteName('#__joomleague_' . $table, 'item'))
			->where('item.published = 1')->where('item.id > :afterId')
			->bind(':afterId', $afterId, ParameterType::INTEGER)->order('item.id ASC');

		if ($type === 'project' || $type === 'eventreport') {
			$project = $type === 'project' ? 'item' : 'project';
			if ($type === 'eventreport') {
				$query->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = item.project_id AND project.published = 1')
					->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id = item.stage_id AND stage.published = 1')
					->innerJoin($db->quoteName('#__joomleague_project_round', 'round') . ' ON round.id = item.round_id AND round.published = 1')
					->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'profile_version') . ' ON profile_version.id = project.profile_version_id');
			}
			$query->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = ' . $project . '.competition_id AND competition.published = 1')
				->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = ' . $project . '.season_id AND season.published = 1')
				->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = ' . $project . '.sport_type_id AND sport_type.published = 1');
			foreach ([$project, 'competition', 'season'] as $alias) {
				$query->where($db->quoteName($alias . '.access') . ' IN (' . $access . ')');
			}
		} else {
			$query->where($db->quoteName('item.access') . ' IN (' . $access . ')');
		}

		$ids = $db->setQuery($query, 0, $limit)->loadColumn();
		if ($ids === []) return [];
		$app = Factory::getApplication();
		if (!$app instanceof SiteApplication) {
			throw new \RuntimeException('Public URL discovery requires the Joomla site application.');
		}
		// Keep caller-specific cached menus and Route routers out of public discovery.
		$menu = new SiteMenu(['app' => $app, 'db' => $db, 'language' => $app->getLanguage(), 'user' => new User()]);
		$router = new SiteRouter($app, $menu);
		$origin = Uri::getInstance();
		$result = [];
		foreach ($ids as $id) {
			$id = (int) $id;
			$uri = clone $router->build('index.php?option=com_joomleague&view=' . $type . '&' . $parameter . '=' . $id);
			$uri->setScheme($origin->getScheme());
			$uri->setHost($origin->getHost());
			$uri->setPort($origin->getPort());
			$url = preg_replace('/\s/u', '%20', $uri->toString(['scheme', 'host', 'port', 'path', 'query', 'fragment']));
			$result[] = ['id' => $id, 'url' => $url, 'lastmod' => null];
		}
		return $result;
	}
}
