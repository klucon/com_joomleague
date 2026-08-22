<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Table\ProjectTable;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

/**
 * Clones a project's settings and entries (participants) as a starting point for a new
 * season. Stages, rounds, matches, results, lineups and events are deliberately NOT copied
 * — those are generated fresh for the new season. Permissions (asset rules) are also not
 * copied; the new project gets a clean asset like any newly created project.
 */
final class ProjectCloner
{
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	public function clone(int $sourceProjectId, string $newName): int
	{
		$db = $this->database;
		$source = $this->loadSource($sourceProjectId);
		if ($source === null) {
			throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_PROJECT_NOT_FOUND'));
		}

		$db->transactionStart();

		try {
			$newProjectId = $this->cloneProject($source, $newName);
			$entryIdMap = $this->cloneEntries($sourceProjectId, $newProjectId);
			$this->cloneEntryMembers($entryIdMap);
			$this->cloneTemplateConfig($sourceProjectId, $newProjectId);
			$this->cloneRuleConfig($sourceProjectId, $newProjectId);

			$db->transactionCommit();
		} catch (\Throwable $exception) {
			$db->transactionRollback();
			throw $exception;
		}

		return $newProjectId;
	}

	private function loadSource(int $projectId): ?object
	{
		$db = $this->database;
		$query = $db->getQuery(true)->select('*')->from($db->quoteName('#__joomleague_project'))
			->where($db->quoteName('id') . ' = :id')->bind(':id', $projectId, ParameterType::INTEGER);

		$row = $db->setQuery($query)->loadObject();

		return $row ?: null;
	}

	private function cloneProject(object $source, string $newName): int
	{
		$userId = (int) Factory::getApplication()->getIdentity()->id;
		$now = Factory::getDate()->toSql();

		$table = new ProjectTable($this->database);
		$table->bind((array) $source);
		$table->id = 0;
		$table->asset_id = 0;
		$table->uuid = UuidFactory::v4();
		$table->name = $newName;
		$table->alias = '';
		$table->code = null;
		$table->external_code = null;
		$table->checked_out = null;
		$table->checked_out_time = null;
		$table->created = $now;
		$table->created_by = $userId;
		$table->modified = null;
		$table->modified_by = 0;

		if (!$table->check() || !$table->store()) {
			throw new \RuntimeException($table->getError() ?: Text::_('COM_JOOMLEAGUE_ERROR_PROJECT_CLONE_FAILED'));
		}

		return (int) $table->id;
	}

	/** @return array<int,int> old entry id => new entry id */
	private function cloneEntries(int $sourceProjectId, int $newProjectId): array
	{
		$db = $this->database;
		$userId = (int) Factory::getApplication()->getIdentity()->id;
		$now = Factory::getDate()->toSql();

		$query = $db->getQuery(true)->select('*')->from($db->quoteName('#__joomleague_project_entry'))
			->where($db->quoteName('project_id') . ' = :projectId')->bind(':projectId', $sourceProjectId, ParameterType::INTEGER);
		$rows = $db->setQuery($query)->loadObjectList();

		$map = [];
		foreach ($rows as $row) {
			$oldId = (int) $row->id;
			$row->id = null;
			$row->uuid = UuidFactory::v4();
			$row->project_id = $newProjectId;
			$row->created = $now;
			$row->created_by = $userId;
			$row->modified = null;
			$row->modified_by = 0;

			$db->insertObject('#__joomleague_project_entry', $row, 'id');
			$map[$oldId] = (int) $row->id;
		}

		return $map;
	}

	/** @param array<int,int> $entryIdMap */
	private function cloneEntryMembers(array $entryIdMap): void
	{
		if ($entryIdMap === []) {
			return;
		}

		$db = $this->database;
		$userId = (int) Factory::getApplication()->getIdentity()->id;
		$now = Factory::getDate()->toSql();

		$query = $db->getQuery(true)->select('*')->from($db->quoteName('#__joomleague_project_entry_member'))
			->whereIn($db->quoteName('entry_id'), array_keys($entryIdMap));
		$rows = $db->setQuery($query)->loadObjectList();

		foreach ($rows as $row) {
			$row->id = null;
			$row->uuid = UuidFactory::v4();
			$row->entry_id = $entryIdMap[(int) $row->entry_id];
			$row->created = $now;
			$row->created_by = $userId;
			$row->modified = null;
			$row->modified_by = 0;

			$db->insertObject('#__joomleague_project_entry_member', $row, 'id');
		}
	}

	private function cloneTemplateConfig(int $sourceProjectId, int $newProjectId): void
	{
		$db = $this->database;
		$userId = (int) Factory::getApplication()->getIdentity()->id;
		$now = Factory::getDate()->toSql();

		$query = $db->getQuery(true)->select('*')->from($db->quoteName('#__joomleague_project_template_config'))
			->where($db->quoteName('project_id') . ' = :projectId')->bind(':projectId', $sourceProjectId, ParameterType::INTEGER);
		$rows = $db->setQuery($query)->loadObjectList();

		foreach ($rows as $row) {
			$row->id = null;
			$row->project_id = $newProjectId;
			$row->created = $now;
			$row->created_by = $userId;
			$row->modified = null;
			$row->modified_by = 0;

			$db->insertObject('#__joomleague_project_template_config', $row, 'id');
		}
	}

	private function cloneRuleConfig(int $sourceProjectId, int $newProjectId): void
	{
		$db = $this->database;
		$userId = (int) Factory::getApplication()->getIdentity()->id;
		$now = Factory::getDate()->toSql();

		$query = $db->getQuery(true)->select('*')->from($db->quoteName('#__joomleague_project_rule_config'))
			->where($db->quoteName('project_id') . ' = :projectId')->bind(':projectId', $sourceProjectId, ParameterType::INTEGER);
		$row = $db->setQuery($query)->loadObject();

		if ($row === null) {
			return;
		}

		$row->id = null;
		$row->project_id = $newProjectId;
		$row->created = $now;
		$row->created_by = $userId;
		$row->modified = null;
		$row->modified_by = 0;

		$db->insertObject('#__joomleague_project_rule_config', $row, 'id');
	}
}
