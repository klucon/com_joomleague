<?php

declare(strict_types=1);

namespace Joomleague\Plugin\Finder\Joomleague\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Helper;
use Joomla\Component\Finder\Administrator\Indexer\Indexer;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\QueryInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/** Smart Search adapter for public JoomLeague entities. */
final class Joomleague extends Adapter implements SubscriberInterface
{
	use DatabaseAwareTrait;

	protected $context = 'Joomleague';
	protected $extension = 'com_joomleague';
	protected $layout = 'entity';
	protected $type_title = 'JoomLeague';
	protected $table = '#__joomleague_project';
	protected $autoloadLanguage = true;

	protected function setup(): bool
	{
		return true;
	}

	protected function index(Result $item): void
	{
		if (!ComponentHelper::isEnabled($this->extension)) return;
		$item->setLanguage();
		$item->context = 'com_joomleague.' . $item->entity_kind;
		$item->params = new Registry();
		$item->metadata = new Registry();
		$item->summary = Helper::prepareContent((string) $item->summary, $item->params, $item);
		$item->body = Helper::prepareContent((string) $item->body, $item->params, $item);
		$item->url = $this->entityRoute((string) $item->entity_kind, (int) $item->entity_id);
		$item->route = $item->url;
		$item->addTaxonomy('Type', 'JoomLeague');
		$item->addTaxonomy(Text::_('PLG_FINDER_JOOMLEAGUE_TAXONOMY_ENTITY'), Text::_('PLG_FINDER_JOOMLEAGUE_ENTITY_' . strtoupper((string) $item->entity_kind)));
		$item->addTaxonomy('Language', '*');
		$this->indexer->index($item);
	}

	protected function getContentCount(): int
	{
		$total = 0;
		foreach (array_keys($this->definitions()) as $kind) {
			$query = $this->queryFor($kind)->clear('select')->clear('order')->select('COUNT(*)');
			$total += (int) $this->db->setQuery($query)->loadResult();
		}
		return $total;
	}

	/** @return list<Result> */
	protected function getItems($offset, $limit, $query = null): array
	{
		$items = [];
		$remaining = max(0, (int) $limit);
		$offset = max(0, (int) $offset);
		foreach (array_keys($this->definitions()) as $kind) {
			$countQuery = $this->queryFor($kind)->clear('select')->clear('order')->select('COUNT(*)');
			$count = (int) $this->db->setQuery($countQuery)->loadResult();
			if ($offset >= $count) { $offset -= $count; continue; }
			$rows = $this->db->setQuery($this->queryFor($kind), $offset, $remaining)->loadAssocList();
			foreach ($rows as $row) {
				$item = ArrayHelper::toObject($row, Result::class);
				$item->type_id = $this->type_id;
				$item->layout = $this->layout;
				$items[] = $item;
			}
			$remaining -= count($rows);
			$offset = 0;
			if ($remaining < 1) break;
		}
		return $items;
	}

	protected function getListQuery($query = null): QueryInterface
	{
		return $query instanceof QueryInterface ? $query : $this->queryFor('project');
	}

	public function onFinderGarbageCollection(): int
	{
		$current = [];
		foreach (array_keys($this->definitions()) as $kind) {
			$query = $this->queryFor($kind)->clear('select')->clear('order')->select($kind . '.id');
			foreach ($this->db->setQuery($query)->loadColumn() as $id) $current[$this->entityRoute($kind, (int) $id)] = true;
		}
		$query = $this->db->getQuery(true)->select(['link_id', 'url'])->from($this->db->quoteName('#__finder_links'))->where('type_id = ' . (int) $this->getTypeId());
		$removed = 0;
		foreach ($this->db->setQuery($query)->loadObjectList() as $link) {
			if (!isset($current[(string) $link->url])) { $this->indexer->remove((int) $link->link_id); $removed++; }
		}
		return $removed;
	}

	/** @return array<string,array{table:string}> */
	private function definitions(): array
	{
		return [
			'project' => ['table' => '#__joomleague_project'],
			'club' => ['table' => '#__joomleague_club'],
			'team' => ['table' => '#__joomleague_team'],
			'person' => ['table' => '#__joomleague_person'],
			'venue' => ['table' => '#__joomleague_venue'],
		];
	}

	private function queryFor(string $kind): QueryInterface
	{
		$db = $this->db;
		$common = static fn (string $kind, string $id, string $title, string $alias, string $summary, string $body, string $image, string $access, string $created, string $modified): array => [
			"CONCAT('{$kind}:', {$id}) AS id", "{$id} AS entity_id", $db->quote($kind) . ' AS entity_kind', "{$title} AS title", "{$alias} AS alias",
			"{$summary} AS summary", "{$body} AS body", "{$image} AS imageUrl", '1 AS state', "{$access} AS access", "{$created} AS start_date", "{$modified} AS modified", "'*' AS language",
		];

		switch ($kind) {
			case 'club':
				return $db->getQuery(true)->select($common('club','club.id','club.name','club.alias','club.description',"CONCAT(club.short_name, ' ', COALESCE(club.country_code, ''))",'club.logo','club.access','club.created','club.modified'))->from($db->quoteName('#__joomleague_club','club'))->where('club.published = 1')->order('club.id ASC');
			case 'team':
				return $db->getQuery(true)->select($common('team','team.id','team.name','team.alias','team.description',"CONCAT(team.middle_name, ' ', team.short_name, ' ', COALESCE(club.name, ''))","COALESCE(NULLIF(team.logo, ''), team.picture)",'team.access','team.created','team.modified'))->from($db->quoteName('#__joomleague_team','team'))->leftJoin($db->quoteName('#__joomleague_club','club').' ON club.id=team.club_id AND club.published=1')->where('team.published = 1')->order('team.id ASC');
			case 'person':
				return $db->getQuery(true)->select($common('person','person.id',"TRIM(CONCAT(person.first_name, ' ', person.last_name))","CONCAT('person-', person.id)",'person.description',"CONCAT(person.nickname, ' ', COALESCE(person.country_code, ''))",'person.picture','person.access','person.created','person.modified'))->from($db->quoteName('#__joomleague_person','person'))->where('person.published = 1')->where("TRIM(CONCAT(person.first_name, ' ', person.last_name)) <> ''")->order('person.id ASC');
			case 'venue':
				return $db->getQuery(true)->select($common('venue','venue.id','venue.name','venue.alias','venue.description',"CONCAT(venue.nickname, ' ', venue.address, ' ', venue.postal_code, ' ', venue.city, ' ', venue.region, ' ', COALESCE(venue.country_code, ''))",'venue.picture','venue.access','venue.created','venue.modified'))->from($db->quoteName('#__joomleague_venue','venue'))->where('venue.published = 1')->order('venue.id ASC');
			default:
				return $db->getQuery(true)->select($common('project','project.id','project.name','project.alias','project.description',"CONCAT(competition.name, ' ', season.name, ' ', sport_type.name, ' ', COALESCE(project.code, ''))",'project.picture','GREATEST(project.access, competition.access, season.access)','project.start_date','NULL'))->from($db->quoteName('#__joomleague_project','project'))->innerJoin($db->quoteName('#__joomleague_competition','competition').' ON competition.id=project.competition_id AND competition.published=1')->innerJoin($db->quoteName('#__joomleague_season','season').' ON season.id=project.season_id AND season.published=1')->innerJoin($db->quoteName('#__joomleague_sport_type','sport_type').' ON sport_type.id=project.sport_type_id AND sport_type.published=1')->where('project.published = 1')->order('project.id ASC');
		}
	}

	private function entityRoute(string $kind, int $id): string
	{
		$key = match ($kind) { 'project' => 'project_id', 'club' => 'club_id', 'team' => 'team_id', 'person' => 'person_id', 'venue' => 'venue_id', default => 'id' };
		return 'index.php?option=com_joomleague&view=' . $kind . '&' . $key . '=' . $id;
	}
}
