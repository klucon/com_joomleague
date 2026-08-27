<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\ComponentAdapter;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\File;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleValidator;
use Joomleague\Component\Joomleague\Administrator\Service\EntryModelValidator;
use Joomleague\Component\Joomleague\Domain\Service\StandingsContractValidator;
use Joomleague\Component\Joomleague\Administrator\Service\SportProfileSchemaValidator;

defined('_JEXEC') or die;

final class Com_JoomleagueInstallerScript
{
	private const DEVELOPMENT_PROFILE_SYNC = true;

	private const OBSOLETE_ADMIN_FILES = [
		'sql/updates/mysql/6.2.0.sql',
		'sql/updates/mysql/6.2.0-20260801.sql',
		'sql/updates/postgresql/6.2.0.sql',
		'sql/updates/postgresql/6.2.0-20260801.sql',
	];

	private const OBSOLETE_MEDIA_FILES = [
		'joomleague.joomla.asset.json',
	];

	private const OBSOLETE_SITE_FILES = [
		'src/Model/ProgramitemModel.php',
		'src/View/Programitem/HtmlView.php',
		'tmpl/programitem/default.php',
		'tmpl/programitem/default.xml',
	];

	public function preflight(string $type, ComponentAdapter $adapter): bool
	{
		if ($type !== 'update') {
			return true;
		}

		$database = Factory::getContainer()->get(DatabaseInterface::class);
		$table = '#__joomleague_sport_type';

		if (!in_array($database->replacePrefix($table), $database->getTableList(), true)) {
			return true;
		}

		foreach ($database->getTableKeys($table) as $key) {
			$name = $key->Key_name ?? $key->idxName ?? '';

			if ($name === 'uq_jl_sport_type_profile_binding') {
				return true;
			}
		}

		if ($database->getName() === 'pgsql') {
			$sql = 'ALTER TABLE ' . $database->quoteName($table)
				. ' ADD CONSTRAINT ' . $database->quoteName('uq_jl_sport_type_profile_binding')
				. ' UNIQUE (' . $database->quoteName('id') . ', ' . $database->quoteName('profile_version_id') . ')';
		} else {
			$sql = 'ALTER TABLE ' . $database->quoteName($table)
				. ' ADD UNIQUE KEY ' . $database->quoteName('uq_jl_sport_type_profile_binding')
				. ' (' . $database->quoteName('id') . ', ' . $database->quoteName('profile_version_id') . ')';
		}

		$database->setQuery($sql)->execute();

		return true;
	}

	public function postflight(string $type, ComponentAdapter $adapter): bool
	{
		if (!in_array($type, ['install', 'update'], true)) {
			return true;
		}

		$this->removeObsoleteFiles();
		try {
			$this->synchroniseBundledProfiles();
			$this->synchroniseGuidedTours();
		} catch (Throwable $exception) {
			Factory::getApplication()->enqueueMessage($exception->getMessage(), 'error');

			return false;
		}

		return true;
	}

	private function synchroniseGuidedTours(): void
	{
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		$tableNames = $database->getTableList();

		foreach (['#__guidedtours', '#__guidedtour_steps'] as $tableName) {
			if (!in_array($database->replacePrefix($tableName), $tableNames, true)) {
				return;
			}
		}

		$tours = [
			[
				'uid' => 'com_joomleague.getting-started',
				'title' => 'COM_JOOMLEAGUE_GUIDEDTOUR_GETTING_STARTED_TITLE',
				'description' => 'COM_JOOMLEAGUE_GUIDEDTOUR_GETTING_STARTED_DESCRIPTION',
				'steps' => [
					['overview', 'bottom', '.container-fluid > .card:first-of-type'],
					['profiles', 'right', 'a[href*="view=sportprofiles"]'],
					['competitions', 'right', 'a[href*="view=competitions"]'],
					['projects', 'right', 'a[href*="view=projects"]'],
					['templates', 'left', 'a[href*="view=templates"]'],
					['tools', 'left', 'a[href*="view=tools"]'],
				],
			],
			[
				'uid' => 'com_joomleague.create-competition',
				'title' => 'COM_JOOMLEAGUE_GUIDEDTOUR_CREATE_COMPETITION_TITLE',
				'description' => 'COM_JOOMLEAGUE_GUIDEDTOUR_CREATE_COMPETITION_DESCRIPTION',
				'steps' => [
					['prepare', 'center', ''],
					['sporttype_redirect', 'center', '', 1, 1, 'administrator/index.php?option=com_joomleague&view=sporttype&layout=edit'],
					['sporttype_name', 'right', 'body.view-sporttype #jform_name', 2, 2],
					['sporttype_code', 'right', 'body.view-sporttype #jform_code', 2, 2],
					['sporttype_profile', 'right', 'body.view-sporttype #jform_profile_version_id', 2, 6],
					['sporttype_save', 'bottom', 'body.view-sporttype #toolbar-save button', 2, 1],
					['competition_redirect', 'center', '', 1, 1, 'administrator/index.php?option=com_joomleague&view=competition&layout=edit'],
					['competition_name', 'right', 'body.view-competition #jform_name', 2, 2],
					['competition_save', 'bottom', 'body.view-competition #toolbar-save button', 2, 1],
					['season_redirect', 'center', '', 1, 1, 'administrator/index.php?option=com_joomleague&view=season&layout=edit'],
					['season_name', 'right', 'body.view-season #jform_name', 2, 2],
					['season_save', 'bottom', 'body.view-season #toolbar-save button', 2, 1],
					['project_redirect', 'center', '', 1, 1, 'administrator/index.php?option=com_joomleague&view=project&layout=edit'],
					['project_name', 'right', 'body.view-project #jform_name', 2, 2],
					['project_competition', 'right', 'body.view-project #jform_competition_id', 2, 6],
					['project_season', 'right', 'body.view-project #jform_season_id', 2, 6],
					['project_sporttype', 'right', 'body.view-project #jform_sport_type_id', 2, 6],
					['project_type', 'right', 'body.view-project #jform_project_type', 2, 6],
					['project_save', 'bottom', 'body.view-project #toolbar-save button', 2, 1],
					['project_open', 'bottom', '#projectList tbody tr:first-child th a'],
				],
			],
		];

		$database->transactionStart();

		try {
			foreach ($tours as $tour) {
				$this->synchroniseGuidedTour($database, $tour);
			}

			$database->transactionCommit();
		} catch (Throwable $exception) {
			$database->transactionRollback();
			throw $exception;
		}
	}

	/**
	 * @param array{uid:string,title:string,description:string,steps:list<array{0:string,1:string,2:string,3?:int,4?:int,5?:string}>} $definition
	 */
	private function synchroniseGuidedTour(DatabaseInterface $database, array $definition): void
	{
		$uid = $definition['uid'];
		$query = $database->getQuery(true)
			->select($database->quoteName('id'))
			->from($database->quoteName('#__guidedtours'))
			->where($database->quoteName('uid') . ' = :uid')
			->bind(':uid', $uid);
		$tourId = (int) $database->setQuery($query)->loadResult();
		$now = Factory::getDate()->toSql();
		$tour = (object) [
			'id' => $tourId,
			'title' => $definition['title'],
			'uid' => $uid,
			'description' => $definition['description'],
			'ordering' => 0,
			'extensions' => '["com_joomleague"]',
			'url' => 'administrator/index.php?option=com_joomleague&view=dashboard',
			'created' => $now,
			'created_by' => 0,
			'modified' => $now,
			'modified_by' => 0,
			'published' => 1,
			'language' => '*',
			'note' => '',
			'access' => 1,
			'autostart' => 0,
		];

		if ($tourId > 0) {
			unset($tour->created, $tour->created_by);
			$database->updateObject('#__guidedtours', $tour, 'id');
		} else {
			$database->insertObject('#__guidedtours', $tour, 'id');
			$tourId = (int) $tour->id;
		}

		$query = $database->getQuery(true)
			->delete($database->quoteName('#__guidedtour_steps'))
			->where($database->quoteName('tour_id') . ' = :tourId')
			->bind(':tourId', $tourId, ParameterType::INTEGER);
		$database->setQuery($query)->execute();

		foreach ($definition['steps'] as $ordering => $stepDefinition) {
			[$code, $position, $target] = $stepDefinition;
			$type = $stepDefinition[3] ?? 0;
			$interactiveType = $stepDefinition[4] ?? 1;
			$url = $stepDefinition[5] ?? '';
			$step = (object) [
				'tour_id' => $tourId,
				'title' => 'COM_JOOMLEAGUE_GUIDEDTOUR_STEP_' . strtoupper($code) . '_TITLE',
				'published' => 1,
				'description' => 'COM_JOOMLEAGUE_GUIDEDTOUR_STEP_' . strtoupper($code) . '_DESCRIPTION',
				'ordering' => $ordering + 1,
				'position' => $position,
				'target' => $target,
				'type' => $type,
				'interactive_type' => $interactiveType,
				'url' => $url,
				'created' => $now,
				'created_by' => 0,
				'modified' => $now,
				'modified_by' => 0,
				'language' => '*',
				'note' => '',
				'params' => '{"required":1,"requiredvalue":""}',
			];
			$database->insertObject('#__guidedtour_steps', $step, 'id');
		}
	}

	/** @return array{processed: int} */
	public function synchroniseBundledProfiles(): array
	{
		$this->loadInstallerDependencies();
		$profileDirectory = JPATH_ADMINISTRATOR . '/components/com_joomleague/resources/sport-profiles';
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		$processed = 0;
		$database->transactionStart();

		try {
			foreach ($this->loadProfiles($profileDirectory) as $profile) {
				$this->synchroniseProfile($database, $profile);
				$processed++;
			}
			$database->transactionCommit();
		} catch (Throwable $exception) {
			$database->transactionRollback();
			throw $exception;
		}

		return compact('processed');
	}

	private function loadInstallerDependencies(): void
	{
		$services = [
			ProjectRuleValidator::class => 'ProjectRuleValidator.php',
			EntryModelValidator::class => 'EntryModelValidator.php',
			StandingsContractValidator::class => 'StandingsContractValidator.php',
			SportProfileSchemaValidator::class => 'SportProfileSchemaValidator.php',
		];
		$serviceDirectory = JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/';

		foreach ($services as $className => $fileName) {
			if (!class_exists($className, false)) {
				$file = $serviceDirectory . $fileName;

				if (!is_file($file)) {
					throw new RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_INSTALLER_DEPENDENCY_MISSING'));
				}

				require_once $file;
			}

			if (!class_exists($className, false)) {
				throw new RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_INSTALLER_DEPENDENCY_MISSING'));
			}
		}
	}

	private function removeObsoleteFiles(): void
	{
		$groups = [
			JPATH_ADMINISTRATOR . '/components/com_joomleague/' => self::OBSOLETE_ADMIN_FILES,
			JPATH_ROOT . '/components/com_joomleague/' => self::OBSOLETE_SITE_FILES,
			JPATH_ROOT . '/media/com_joomleague/' => self::OBSOLETE_MEDIA_FILES,
		];

		foreach ($groups as $root => $files) {
			foreach ($files as $relativePath) {
				$path = $root . $relativePath;

				if (is_file($path) && !File::delete($path)) {
					throw new RuntimeException(Text::sprintf('COM_JOOMLEAGUE_ERROR_OBSOLETE_FILE_DELETE_FAILED', $relativePath));
				}
			}
		}
	}

	/**
	 * @return iterable<array{data: array<string, mixed>, payload: string, checksum: string, file: string}>
	 */
	private function loadProfiles(string $directory): iterable
	{
		$files = glob($directory . '/*.json') ?: [];
		sort($files, SORT_STRING);

		if ($files === []) {
			throw new RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_NO_BUNDLED_PROFILES'));
		}

		foreach ($files as $file) {
			$payload = file_get_contents($file);

			if ($payload === false) {
				throw new RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_PROFILE_READ_FAILED'));
			}

			try {
				$data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
			} catch (JsonException $exception) {
				throw new RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_PROFILE_JSON_INVALID'), 0, $exception);
			}

			$this->validateProfile($data);

			yield [
				'data' => $data,
				'payload' => $payload,
				'checksum' => hash('sha256', $payload),
				'file' => basename($file),
			];
		}
	}

	/** @param array<string, mixed> $profile */
	private function validateProfile(array $profile): void
	{
		foreach (['schema_version', 'code', 'version', 'name_key', 'description_key'] as $field) {
			if (!isset($profile[$field]) || !is_string($profile[$field]) || trim($profile[$field]) === '') {
				throw new RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_PROFILE_REQUIRED_FIELD'));
			}
		}

		if (preg_match('/^[a-z][a-z0-9_]*$/', $profile['code']) !== 1) {
			throw new RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_PROFILE_CODE_INVALID'));
		}

		if (preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $profile['version']) !== 1) {
			throw new RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_PROFILE_VERSION_INVALID'));
		}

		(new ProjectRuleValidator())->validateProfileSchema($profile);
		(new EntryModelValidator())->validate($profile);
		(new SportProfileSchemaValidator())->validate($profile);
	}

	/** @param array{data: array<string, mixed>, payload: string, checksum: string, file: string} $profile */
	private function synchroniseProfile(DatabaseInterface $database, array $profile): void
	{
		$data = $profile['data'];
		$profileId = $this->findProfileId($database, $data['code']);

		if ($profileId === null) {
			$query = $database->getQuery(true)
				->insert($database->quoteName('#__joomleague_sport_profile'))
				->columns($database->quoteName(['code', 'name_key', 'description_key', 'published']))
				->values(':code, :name_key, :description_key, 1')
				->bind(':code', $data['code'])
				->bind(':name_key', $data['name_key'])
				->bind(':description_key', $data['description_key']);
			$database->setQuery($query)->execute();
			$profileId = (int) $database->insertid();
		} else {
			$query = $database->getQuery(true)
				->update($database->quoteName('#__joomleague_sport_profile'))
				->set($database->quoteName('name_key') . ' = :name_key')
				->set($database->quoteName('description_key') . ' = :description_key')
				->where($database->quoteName('id') . ' = :id')
				->bind(':name_key', $data['name_key'])
				->bind(':description_key', $data['description_key'])
				->bind(':id', $profileId);
			$database->setQuery($query)->execute();
		}

		$existingChecksum = $this->findVersionChecksum($database, (int) $profileId, $data['version']);

		if ($existingChecksum !== null) {
			if (!hash_equals($existingChecksum, $profile['checksum'])) {
				if (!self::DEVELOPMENT_PROFILE_SYNC) throw new RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_PROFILE_VERSION_IMMUTABLE'));
				if (!$this->updateDevelopmentProfileVersion($database, (int) $profileId, $profile)) throw new RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_PROFILE_VERSION_IMMUTABLE'));
			}

			return;
		}

		if (self::DEVELOPMENT_PROFILE_SYNC && $this->updateDevelopmentProfileVersion($database, (int) $profileId, $profile)) return;

		$superseded = 'superseded';
		$query = $database->getQuery(true)
			->update($database->quoteName('#__joomleague_sport_profile_version'))
			->set($database->quoteName('state') . ' = :superseded')
			->where($database->quoteName('profile_id') . ' = :profile_id')
			->where($database->quoteName('state') . ' = ' . $database->quote('active'))
			->bind(':superseded', $superseded)
			->bind(':profile_id', $profileId);
		$database->setQuery($query)->execute();

		$source = 'bundled';
		$state = 'active';
		$query = $database->getQuery(true)
			->insert($database->quoteName('#__joomleague_sport_profile_version'))
			->columns($database->quoteName([
				'profile_id', 'schema_version', 'profile_version', 'payload_json',
				'payload_checksum', 'source', 'state',
			]))
			->values(':profile_id, :schema_version, :profile_version, :payload_json, :payload_checksum, :source, :state')
			->bind(':profile_id', $profileId)
			->bind(':schema_version', $data['schema_version'])
			->bind(':profile_version', $data['version'])
			->bind(':payload_json', $profile['payload'])
			->bind(':payload_checksum', $profile['checksum'])
			->bind(':source', $source)
			->bind(':state', $state);
		$database->setQuery($query)->execute();
	}

	/** @param array{data: array<string, mixed>, payload: string, checksum: string, file: string} $profile */
	private function updateDevelopmentProfileVersion(DatabaseInterface $database, int $profileId, array $profile): bool
	{
		$query = $database->getQuery(true)
			->select($database->quoteName('id'))
			->from($database->quoteName('#__joomleague_sport_profile_version'))
			->where($database->quoteName('profile_id') . ' = :profile_id')
			->where($database->quoteName('source') . ' = ' . $database->quote('bundled'))
			->where($database->quoteName('state') . ' = ' . $database->quote('active'))
			->bind(':profile_id', $profileId);
		$versionId = $database->setQuery($query)->loadResult();

		if ($versionId === null) return false;

		$data = $profile['data'];
		$query = $database->getQuery(true)
			->update($database->quoteName('#__joomleague_sport_profile_version'))
			->set($database->quoteName('schema_version') . ' = :schema_version')
			->set($database->quoteName('profile_version') . ' = :profile_version')
			->set($database->quoteName('payload_json') . ' = :payload_json')
			->set($database->quoteName('payload_checksum') . ' = :payload_checksum')
			->where($database->quoteName('id') . ' = :id')
			->bind(':schema_version', $data['schema_version'])
			->bind(':profile_version', $data['version'])
			->bind(':payload_json', $profile['payload'])
			->bind(':payload_checksum', $profile['checksum'])
			->bind(':id', $versionId);
		$database->setQuery($query)->execute();

		return true;
	}

	private function findProfileId(DatabaseInterface $database, string $code): ?int
	{
		$query = $database->getQuery(true)
			->select($database->quoteName('id'))
			->from($database->quoteName('#__joomleague_sport_profile'))
			->where($database->quoteName('code') . ' = :code')
			->bind(':code', $code);
		$value = $database->setQuery($query)->loadResult();

		return $value === null ? null : (int) $value;
	}

	private function findVersionChecksum(DatabaseInterface $database, int $profileId, string $version): ?string
	{
		$query = $database->getQuery(true)
			->select($database->quoteName('payload_checksum'))
			->from($database->quoteName('#__joomleague_sport_profile_version'))
			->where($database->quoteName('profile_id') . ' = :profile_id')
			->where($database->quoteName('profile_version') . ' = :profile_version')
			->bind(':profile_id', $profileId)
			->bind(':profile_version', $version);
		$value = $database->setQuery($query)->loadResult();

		return $value === null ? null : (string) $value;
	}
}
