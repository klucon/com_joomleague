<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class ProjectProfileMigrationService
{
	public function __construct(private readonly DatabaseInterface $database) {}

	public function inspect(int $projectId, int $targetId = 0): array
	{
		$db = $this->database;
		$query = $db->getQuery(true)->select(['project.*', 'version.profile_id', 'version.profile_version', 'version.schema_version', 'version.payload_json', 'version.payload_checksum', 'profile.name_key'])
			->from($db->quoteName('#__joomleague_project', 'project'))
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->innerJoin($db->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
			->where('project.id = :id')->bind(':id', $projectId, ParameterType::INTEGER);
		$current = $db->setQuery($query)->loadAssoc();
		if (!$current) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_NOT_FOUND'));
		if ((int) ($current['sport_variant_version_id'] ?? 0) > 0) {
			if ($targetId > 0) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROJECT_VARIANT_LOCKED'));
			$variant = (new SportVariantService($db))->revision((int) $current['sport_variant_version_id']);
			$current['profile_version'] = $variant['profile']['version'];
			return $this->inspectVariant($current, $variant);
		}
		$profileId = (int) $current['profile_id'];
		$query = $db->getQuery(true)->select('*')->from($db->quoteName('#__joomleague_sport_profile_version'))
			->where('profile_id = :profile')->bind(':profile', $profileId, ParameterType::INTEGER)
			->where('state = ' . $db->quote('active'));
		$target = null;
		foreach ($db->setQuery($query)->loadAssocList() as $version) {
			if (($targetId === 0 || (int) $version['id'] === $targetId)
				&& version_compare($version['profile_version'], $current['profile_version'], '>')
				&& ($target === null || version_compare($version['profile_version'], $target['profile_version'], '>'))) $target = $version;
		}
		if ($targetId > 0 && $target === null) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_INVALID_TARGET'));
		$history = $db->getQuery(true)->select(['history.created', 'history.created_by', 'old.profile_version AS from_version', 'new.profile_version AS to_version', 'actor.name AS actor_name'])
			->from($db->quoteName('#__joomleague_project_profile_migration', 'history'))
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'old') . ' ON old.id = history.from_version_id')
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'new') . ' ON new.id = history.to_version_id')
			->leftJoin($db->quoteName('#__users', 'actor') . ' ON actor.id = history.created_by')
			->where('history.project_id = :project')->bind(':project', $projectId, ParameterType::INTEGER)->order('history.id DESC');
		$result = ['current' => $current, 'target' => $target, 'changes' => [], 'blockers' => [], 'checksum' => '', 'history' => $db->setQuery($history)->loadAssocList()];
		if ($target === null) return $result;
		$from = json_decode($current['payload_json'], true, 512, JSON_THROW_ON_ERROR);
		$to = json_decode($target['payload_json'], true, 512, JSON_THROW_ON_ERROR);
		foreach ([$current, $target] as $version) {
			if (!hash_equals($version['payload_checksum'], hash('sha256', $version['payload_json']))) $result['blockers'][] = Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_CHECKSUM_ERROR');
		}
		$result['changes'] = (new ProfileMigrationPlan())->compare($from, $to);
		$profileLayers = [];
		foreach (['from' => (int) $current['profile_version_id'], 'to' => (int) $target['id']] as $side => $versionId) {
			$query = $db->getQuery(true)->select($db->quoteName(['template_code', 'schema_version', 'params_json', 'params_checksum', 'published']))
				->from($db->quoteName('#__joomleague_profile_template_config'))
				->where('profile_version_id = :version')->bind(':version', $versionId, ParameterType::INTEGER)->order('template_code ASC');
			$profileLayers[$side] = $db->setQuery($query)->loadAssocList();
		}
		if ($profileLayers['from'] !== $profileLayers['to']) {
			$result['changes'][] = ['path' => '/profile_template_overrides', 'from' => $profileLayers['from'], 'to' => $profileLayers['to'], 'compatible' => false];
		}
		foreach ($result['changes'] as $change) if (!$change['compatible']) $result['blockers'][] = $change['path'];
		try {
			(new SportProfileSchemaValidator())->validate($to);
			$validator = new ProjectRuleValidator();
			$validator->validateProfileSchema($to);
			$overrides = (new ProjectRuleConfigRepository($db))->get((int) $projectId);
			$validator->validateOverrides($to, $overrides);
		} catch (\Throwable $error) {
			$result['blockers'][] = $error->getMessage();
		}
		// Include sparse configuration checksums so a stale review cannot authorise a later state.
		$config = [];
		foreach (['project_rule_config', 'project_template_config'] as $table) {
			$query = $db->getQuery(true)->select('*')->from($db->quoteName('#__joomleague_' . $table))
				->where('project_id = :project')->bind(':project', $projectId, ParameterType::INTEGER)->order('id ASC');
			$config[$table] = $db->setQuery($query)->loadAssocList();
		}
		$result['checksum'] = hash('sha256', json_encode([$current, $target, $config, $profileLayers, $result['changes'], $result['blockers']], JSON_THROW_ON_ERROR));
		return $result;
	}

	/** Read-only preview; variant revisions cannot use the base-profile apply endpoint. */
	private function inspectVariant(array $current, array $variant): array
	{
		$result = ['current' => $current, 'target' => null, 'changes' => [], 'blockers' => [], 'checksum' => '', 'history' => [], 'variant' => $variant, 'variant_target' => null, 'impact' => []];
		$service = new SportVariantService($this->database);
		$historyQuery = $this->database->getQuery(true)->select(['h.created', 'h.created_by', 'a.name AS actor_name', 'old.payload_json AS old_payload', 'new.payload_json AS new_payload'])->from($this->database->quoteName('#__joomleague_project_profile_migration', 'h'))
			->innerJoin('#__joomleague_sport_profile_version old ON old.id = h.from_version_id')->innerJoin('#__joomleague_sport_profile_version new ON new.id = h.to_version_id')->leftJoin('#__users a ON a.id = h.created_by')->where('h.project_id = :project')->bind(':project', $current['id'], ParameterType::INTEGER)->order('h.id DESC');
		foreach ($this->database->setQuery($historyQuery)->loadAssocList() as $row) {
			$old = json_decode($row['old_payload'], true, 512, JSON_THROW_ON_ERROR);
			$new = json_decode($row['new_payload'], true, 512, JSON_THROW_ON_ERROR);
			$row['from_version'] = $old['variant']['revision'] ?? $old['version'];
			$row['to_version'] = $new['variant']['revision'] ?? $new['version'];
			unset($row['old_payload'], $row['new_payload']);
			$result['history'][] = $row;
		}
		$ownIds = array_map(static fn (array $row): int => (int) $row['id'], $service->revisions((int) $variant['variant_id']));
		foreach ($service->options() as $option) {
			$candidate = (int) $option['id'];
			// Restrict candidates to this identity, not merely the same sport family.
			if (!in_array($candidate, $ownIds, true)) continue;
			if (!version_compare($option['revision'], $variant['revision'], '>')) continue;
			$revision = $service->revision($candidate);
			if ((int) $revision['variant_id'] !== (int) $variant['variant_id']) continue;
			if ($result['variant_target'] === null || version_compare($revision['revision'], $result['variant_target']['revision'], '>')) {
				$result['variant_target'] = $revision;
			}
		}
		if ($result['variant_target'] === null) return $result;
		$from = json_decode($current['payload_json'], true, 512, JSON_THROW_ON_ERROR);
		if (!hash_equals($current['payload_checksum'], hash('sha256', $current['payload_json']))) {
			$result['blockers'][] = Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_CHECKSUM_ERROR');
		}
		unset($from['variant']);
		$to = $result['variant_target']['profile'];
		$result['changes'] = (new ProfileMigrationPlan())->compare($from, $to);
		foreach ($result['changes'] as $change) {
			if (!$change['compatible']) $result['blockers'][] = $change['path'];
		}
		try {
			$validator = new ProjectRuleValidator();
			$validator->validateProfileSchema($to);
			$validator->validateOverrides($to, (new ProjectRuleConfigRepository($this->database))->get((int) $current['id']));
		} catch (\Throwable $error) {
			$result['blockers'][] = $error->getMessage();
		}
		$db = $this->database;
		$projectId = (int) $current['id'];
		foreach (['project_entry', 'project_stage', 'project_match', 'standing_snapshot'] as $table) {
			$query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_' . $table))->where('project_id = :project')->bind(':project', $projectId, ParameterType::INTEGER);
			$result['impact'][$table] = (int) $db->setQuery($query)->loadResult();
		}
		$config = [];
		foreach (['project_rule_config', 'project_template_config'] as $table) {
			$query = $db->getQuery(true)->select('*')->from($db->quoteName('#__joomleague_' . $table))->where('project_id = :project')->bind(':project', $projectId, ParameterType::INTEGER)->order('id ASC');
			$config[$table] = $db->setQuery($query)->loadAssocList();
		}
		$layers = [];
		$targetVersion = (int) ($result['variant_target']['compiled_profile_version_id'] ?: $result['variant_target']['base_profile_version_id']);
		foreach (['from' => (int) $current['profile_version_id'], 'to' => $targetVersion] as $side => $versionId) {
			$query = $db->getQuery(true)->select($db->quoteName(['template_code', 'schema_version', 'params_json', 'params_checksum', 'published']))->from($db->quoteName('#__joomleague_profile_template_config'))->where('profile_version_id = :version')->bind(':version', $versionId, ParameterType::INTEGER)->order('template_code ASC');
			$layers[$side] = $db->setQuery($query)->loadAssocList();
		}
		if ($layers['from'] !== $layers['to']) $result['blockers'][] = '/profile_template_overrides';
		$result['checksum'] = hash('sha256', json_encode([$current, $result['variant_target']['id'], $result['variant_target']['payload_checksum'], $config, $layers, $result['impact'], $result['changes'], $result['blockers']], JSON_THROW_ON_ERROR));
		return $result;
	}

	public function migrateVariant(int $projectId, int $revisionId, string $checksum, int $actorId): void
	{
		if ($actorId < 1 || preg_match('/^[a-f0-9]{64}$/', $checksum) !== 1) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_REVIEW_REQUIRED'));
		$review = $this->inspect($projectId);
		if ((int) ($review['variant_target']['id'] ?? 0) !== $revisionId || $review['blockers'] !== [] || !hash_equals($review['checksum'], $checksum)) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_REVIEW_REQUIRED'));
		// Materialization owns its transaction. A reusable contract may survive a rejected review.
		$compiledId = (new SportVariantService($this->database))->materialize($revisionId, (int) $review['current']['profile_id'], $actorId);
		$db = $this->database;
		$db->transactionStart();
		try {
			$locked = $db->setQuery('SELECT id, checked_out FROM ' . $db->quoteName('#__joomleague_project') . ' WHERE id = ' . $projectId . ' FOR UPDATE')->loadAssoc();
			if (!$locked || ((int) $locked['checked_out'] > 0 && (int) $locked['checked_out'] !== $actorId)) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_LOCKED'));
			$variantId = (int) $review['variant']['variant_id'];
			$db->setQuery('SELECT id FROM ' . $db->quoteName('#__joomleague_sport_variant') . ' WHERE id = ' . $variantId . ' FOR UPDATE')->loadResult();
			$profileId = (int) $review['current']['profile_id'];
			$db->setQuery('SELECT id FROM ' . $db->quoteName('#__joomleague_sport_profile') . ' WHERE id = ' . $profileId . ' FOR UPDATE')->loadResult();
			foreach (['project_rule_config', 'project_template_config'] as $table) {
				$db->setQuery('SELECT id FROM ' . $db->quoteName('#__joomleague_' . $table) . ' WHERE project_id = ' . $projectId . ' ORDER BY id FOR UPDATE')->loadColumn();
			}
			$oldCompiledId = (int) $review['current']['profile_version_id'];
			$db->setQuery('SELECT id FROM ' . $db->quoteName('#__joomleague_profile_template_config') . ' WHERE profile_version_id IN (' . $oldCompiledId . ', ' . $compiledId . ') ORDER BY id FOR UPDATE')->loadColumn();
			$review = $this->inspect($projectId);
			if ((int) ($review['variant_target']['id'] ?? 0) !== $revisionId || $review['blockers'] !== [] || !hash_equals($review['checksum'], $checksum)) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_REVIEW_REQUIRED'));
			$fromId = (int) $review['current']['profile_version_id'];
			$fromRevision = (int) $review['current']['sport_variant_version_id'];
			$now = gmdate('Y-m-d H:i:s');
			$query = $db->getQuery(true)->update($db->quoteName('#__joomleague_project'))->set('profile_version_id = :target')->set('sport_variant_version_id = :revision')->set('modified = :now')->set('modified_by = :actor')->where('id = :project')->where('profile_version_id = :original')->where('sport_variant_version_id = :oldrevision')
				->bind(':target', $compiledId, ParameterType::INTEGER)->bind(':revision', $revisionId, ParameterType::INTEGER)->bind(':now', $now)->bind(':actor', $actorId, ParameterType::INTEGER)->bind(':project', $projectId, ParameterType::INTEGER)->bind(':original', $fromId, ParameterType::INTEGER)->bind(':oldrevision', $fromRevision, ParameterType::INTEGER);
			$db->setQuery($query)->execute();
			if ($db->getAffectedRows() !== 1) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_REVIEW_REQUIRED'));
			$changes = json_encode(['variant' => ['from' => $fromRevision, 'to' => (int) $revisionId], 'changes' => $review['changes']], JSON_THROW_ON_ERROR);
			$query = $db->getQuery(true)->insert($db->quoteName('#__joomleague_project_profile_migration'))->columns($db->quoteName(['project_id', 'from_version_id', 'to_version_id', 'review_checksum', 'changes_json', 'created_by']))->values(':project, :original, :target, :checksum, :changes, :actor')->bind(':project', $projectId, ParameterType::INTEGER)->bind(':original', $fromId, ParameterType::INTEGER)->bind(':target', $compiledId, ParameterType::INTEGER)->bind(':checksum', $checksum)->bind(':changes', $changes)->bind(':actor', $actorId, ParameterType::INTEGER);
			$db->setQuery($query)->execute();
			$query = $db->getQuery(true)->update($db->quoteName('#__joomleague_standing_freshness'))->set('is_dirty = 1')->set('dirty_at = :now')->where('project_id = :project')->bind(':now', $now)->bind(':project', $projectId, ParameterType::INTEGER);
			$db->setQuery($query)->execute();
			$query = $db->getQuery(true)->delete($db->quoteName('#__joomleague_standing_current'))->where('project_id = :project')->bind(':project', $projectId, ParameterType::INTEGER);
			$db->setQuery($query)->execute();
			$db->transactionCommit();
		} catch (\Throwable $error) {
			$db->transactionRollback();
			throw $error;
		}
	}

	public function migrate(int $projectId, int $targetId, string $checksum, int $actorId): void
	{
		if ($actorId < 1 || preg_match('/^[a-f0-9]{64}$/', $checksum) !== 1) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_REVIEW_REQUIRED'));
		$db = $this->database;
		$db->transactionStart();
		try {
			$sql = 'SELECT ' . $db->quoteName('id') . ', ' . $db->quoteName('checked_out')
				. ' FROM ' . $db->quoteName('#__joomleague_project') . ' WHERE ' . $db->quoteName('id') . ' = ' . (int) $projectId . ' FOR UPDATE';
			$locked = $db->setQuery($sql)->loadAssoc();
			if (!$locked || ((int) $locked['checked_out'] > 0 && (int) $locked['checked_out'] !== $actorId)) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_LOCKED'));
			$review = $this->inspect($projectId, $targetId);
			if ($review['blockers'] !== [] || !hash_equals($review['checksum'], $checksum)) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_REVIEW_REQUIRED'));
			$fromId = (int) $review['current']['profile_version_id'];
			$now = gmdate('Y-m-d H:i:s');
			$query = $db->getQuery(true)->update($db->quoteName('#__joomleague_project'))
				->set('profile_version_id = :target')->set('modified = :modified')->set('modified_by = :actor')
				->where('id = :project')->where('profile_version_id = :original')
				->bind(':target', $targetId, ParameterType::INTEGER)->bind(':modified', $now)->bind(':actor', $actorId, ParameterType::INTEGER)
				->bind(':project', $projectId, ParameterType::INTEGER)->bind(':original', $fromId, ParameterType::INTEGER);
			$db->setQuery($query)->execute();
			if ($db->getAffectedRows() !== 1) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_REVIEW_REQUIRED'));
			$changes = json_encode($review['changes'], JSON_THROW_ON_ERROR);
			$query = $db->getQuery(true)->insert($db->quoteName('#__joomleague_project_profile_migration'))
				->columns($db->quoteName(['project_id', 'from_version_id', 'to_version_id', 'review_checksum', 'changes_json', 'created_by']))
				->values(':project, :original, :target, :checksum, :changes, :actor')
				->bind(':project', $projectId, ParameterType::INTEGER)->bind(':original', $fromId, ParameterType::INTEGER)
				->bind(':target', $targetId, ParameterType::INTEGER)->bind(':checksum', $checksum)->bind(':changes', $changes)->bind(':actor', $actorId, ParameterType::INTEGER);
			$db->setQuery($query)->execute();
			$query = $db->getQuery(true)->update($db->quoteName('#__joomleague_standing_freshness'))->set('is_dirty = 1')->set('dirty_at = :now')
				->where('project_id = :project')->bind(':now', $now)->bind(':project', $projectId, ParameterType::INTEGER);
			$db->setQuery($query)->execute();
			$query = $db->getQuery(true)->delete($db->quoteName('#__joomleague_standing_current'))->where('project_id = :project')->bind(':project', $projectId, ParameterType::INTEGER);
			$db->setQuery($query)->execute();
			$db->transactionCommit();
		} catch (\Throwable $error) {
			$db->transactionRollback();
			throw $error;
		}
	}
}
