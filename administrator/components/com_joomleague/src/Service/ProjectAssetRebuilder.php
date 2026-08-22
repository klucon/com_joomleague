<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Asset;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Table\ProjectTable;

/**
 * Heals the com_joomleague asset tree: removes orphan asset rows left over from Joomla's
 * default (unoverridden) asset-name generation, and (re)links every project to a correctly
 * named/parented com_joomleague.project.<id> asset so the Permissions tab works per project.
 */
final class ProjectAssetRebuilder
{
	private const DOMAIN_TABLES = [
		'#__joomleague_project',
		'#__joomleague_competition',
		'#__joomleague_season',
		'#__joomleague_project_stage',
		'#__joomleague_project_round',
		'#__joomleague_project_match',
	];

	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	/** @return array{orphans_removed:int, projects_linked:int} */
	public function rebuild(): array
	{
		return [
			'orphans_removed' => $this->removeOrphanAssets(),
			'projects_linked' => $this->linkProjects(),
		];
	}

	private function removeOrphanAssets(): int
	{
		$db = $this->database;
		$query = $db->getQuery(true)->select($db->quoteName(['id', 'name']))->from($db->quoteName('#__assets'))
			->where($db->quoteName('name') . ' LIKE ' . $db->quote('#__%'));
		$orphans = $db->setQuery($query)->loadObjectList();

		$removed = 0;
		foreach ($orphans as $orphan) {
			if ($this->isReferenced((int) $orphan->id)) {
				continue;
			}

			$asset = new Asset($db);
			if ($asset->load((int) $orphan->id)) {
				$asset->delete();
				$removed++;
			}
		}

		return $removed;
	}

	private function isReferenced(int $assetId): bool
	{
		$db = $this->database;
		foreach (self::DOMAIN_TABLES as $table) {
			$query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName($table))
				->where($db->quoteName('asset_id') . ' = :assetId')->bind(':assetId', $assetId, ParameterType::INTEGER);
			if ((int) $db->setQuery($query)->loadResult() > 0) {
				return true;
			}
		}

		return false;
	}

	private function linkProjects(): int
	{
		$db = $this->database;
		$projectIds = $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_project')))->loadColumn();

		$linked = 0;
		foreach ($projectIds as $projectId) {
			$table = new ProjectTable($db);
			if ($table->load((int) $projectId) && $table->store()) {
				$linked++;
			}
		}

		return $linked;
	}
}
