<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

defined('_JEXEC') or die;

/** Internal persistence boundary; request controllers must enforce ACL and CSRF. */
final class SportVariantService
{
	public function __construct(private readonly DatabaseInterface $db) {}

	public function variants(): array
	{
		$query = $this->db->getQuery(true)->select('v.*')->select('p.name_key')
			->from($this->db->quoteName('#__joomleague_sport_variant', 'v'))
			->join('INNER', $this->db->quoteName('#__joomleague_sport_profile', 'p') . ' ON p.id = v.profile_id')
			->order(['p.code ASC', 'v.name ASC', 'v.id ASC']);
		return $this->db->setQuery($query)->loadAssocList();
	}

	public function bases(int $variantId = 0): array
	{
		$query = $this->db->getQuery(true)->select(['b.id', 'b.profile_id', 'b.profile_version', 'p.name_key'])
			->from($this->db->quoteName('#__joomleague_sport_profile_version', 'b'))
			->join('INNER', $this->db->quoteName('#__joomleague_sport_profile', 'p') . ' ON p.id = b.profile_id')
			->where('p.published = 1')->where('b.source <> ' . $this->db->quote('variant'))->order(['p.code ASC', 'b.id DESC']);
		if ($variantId > 0) {
			$query->join('INNER', $this->db->quoteName('#__joomleague_sport_variant', 'v') . ' ON v.profile_id = b.profile_id')
				->where('v.id = :id')->bind(':id', $variantId, ParameterType::INTEGER);
		}
		return $this->db->setQuery($query)->loadAssocList();
	}

	public function base(int $variantId, int $baseId): array
	{
		$query = $this->db->getQuery(true)->select(['b.payload_json', 'b.payload_checksum'])
			->from($this->db->quoteName('#__joomleague_sport_profile_version', 'b'))
			->join('INNER', $this->db->quoteName('#__joomleague_sport_variant', 'v') . ' ON v.profile_id = b.profile_id')
			->where('v.id = :variant')->where('b.id = :base')
			->where('b.source <> ' . $this->db->quote('variant'))
			->bind(':variant', $variantId, ParameterType::INTEGER)->bind(':base', $baseId, ParameterType::INTEGER);
		$record = $this->db->setQuery($query)->loadAssoc();
		if (!$record || !hash_equals($record['payload_checksum'], hash('sha256', $record['payload_json']))) {
			throw new \InvalidArgumentException('Invalid variant base.');
		}
		$profile = json_decode($record['payload_json'], true, 512, JSON_THROW_ON_ERROR);
		(new SportProfileSchemaValidator())->validate($profile);
		return $profile;
	}

	public function revisions(int $variantId): array
	{
		$query = $this->db->getQuery(true)->select(['r.id', 'r.revision', 'r.payload_checksum', 'r.created', 'b.profile_version'])
			->from($this->db->quoteName('#__joomleague_sport_variant_version', 'r'))
			->join('INNER', $this->db->quoteName('#__joomleague_sport_profile_version', 'b') . ' ON b.id = r.base_profile_version_id')
			->where('r.variant_id = :id')->bind(':id', $variantId, ParameterType::INTEGER)->order('r.id DESC');
		return $this->db->setQuery($query)->loadAssocList();
	}

	public function setPublished(int $variantId, int $state): void
	{
		if ($variantId < 1 || !in_array($state, [0, 1], true)) throw new \InvalidArgumentException('Invalid variant state.');
		$this->write(function () use ($variantId, $state): int {
			$record = $this->db->setQuery('SELECT id FROM ' . $this->db->quoteName('#__joomleague_sport_variant') . ' WHERE id = ' . $variantId . ' FOR UPDATE')->loadResult();
			if (!$record || ($state === 1 && $this->revisions($variantId) === [])) throw new \InvalidArgumentException('A variant needs a revision before publication.');
			$query = $this->db->getQuery(true)->update($this->db->quoteName('#__joomleague_sport_variant'))
				->set('published = :state')->where('id = :id')->bind(':state', $state, ParameterType::INTEGER)->bind(':id', $variantId, ParameterType::INTEGER);
			$this->db->setQuery($query)->execute();
			return (int) $variantId;
		});
	}

	public function create(int $profileId, string $code, string $name, int $userId): int
	{
		$code = trim($code);
		$name = trim($name);
		if ($profileId < 1 || $userId < 0 || !preg_match('/^[a-z][a-z0-9_-]{0,99}$/D', $code)
			|| $name === '' || mb_strlen($name) > 255) {
			throw new \InvalidArgumentException('Invalid sport variant identity.');
		}
		return $this->write(function () use ($profileId, $code, $name, $userId): int {
			$boundId = $profileId;
			$query = 'SELECT id FROM ' . $this->db->quoteName('#__joomleague_sport_profile') . ' WHERE id = ' . $profileId . ' FOR UPDATE';
			if (!$this->db->setQuery($query)->loadResult()) throw new \InvalidArgumentException('Sport profile does not exist.');
			$query = $this->db->getQuery(true)->select('id')->from($this->db->quoteName('#__joomleague_sport_variant'))
				->where('profile_id = :profile')->where('code = :code')->bind(':profile', $boundId, ParameterType::INTEGER)->bind(':code', $code);
			if ($this->db->setQuery($query)->loadResult()) throw new \InvalidArgumentException('Sport variant code already exists.');
			$record = (object) ['uuid' => UuidFactory::v4(), 'profile_id' => $profileId, 'code' => $code,
				'name' => $name, 'published' => 0, 'created' => gmdate('Y-m-d H:i:s'), 'created_by' => $userId];
			$this->db->insertObject('#__joomleague_sport_variant', $record, 'id');
			return (int) $record->id;
		});
	}

	public function addRevision(int $variantId, int $baseVersionId, string $version, array $overrides, int $userId): int
	{
		if ($variantId < 1 || $baseVersionId < 1 || $userId < 0
			|| !preg_match('/^(0|[1-9][0-9]{0,5})\.(0|[1-9][0-9]{0,5})\.(0|[1-9][0-9]{0,5})$/D', $version)
			|| strlen(CanonicalJson::encodeObject($overrides)) > 65536) {
			throw new \InvalidArgumentException('Invalid sport variant revision.');
		}
		return $this->write(function () use ($variantId, $baseVersionId, $version, $overrides, $userId): int {
			$boundId = $variantId;
			$query = 'SELECT profile_id FROM ' . $this->db->quoteName('#__joomleague_sport_variant') . ' WHERE id = ' . $variantId . ' FOR UPDATE';
			$profileId = (int) $this->db->setQuery($query)->loadResult();
			if (!$profileId) throw new \InvalidArgumentException('Sport variant does not exist.');
			$query = 'SELECT * FROM ' . $this->db->quoteName('#__joomleague_sport_profile_version') . ' WHERE id = ' . $baseVersionId . ' FOR UPDATE';
			$base = $this->db->setQuery($query)->loadAssoc();
			if (!$base || $base['source'] === 'variant' || (int) $base['profile_id'] !== $profileId) throw new \InvalidArgumentException('Variant base belongs to a different sport or is not a base profile.');
			$payload = json_decode($base['payload_json'], true, 512, JSON_THROW_ON_ERROR);
			if (!is_array($payload) || !hash_equals($base['payload_checksum'], hash('sha256', $base['payload_json']))) {
				throw new \UnexpectedValueException('Base profile checksum does not match.');
			}
			$compiled = (new SportVariantCompiler())->compile($payload, $overrides);
			$query = $this->db->getQuery(true)->select('revision')->from($this->db->quoteName('#__joomleague_sport_variant_version'))
				->where('variant_id = :id')->bind(':id', $boundId, ParameterType::INTEGER);
			foreach ($this->db->setQuery($query)->loadColumn() as $existing) {
				if (version_compare($version, $existing, '<=')) throw new \InvalidArgumentException('A new revision must be newer than all existing revisions.');
			}
			$record = (object) ['variant_id' => $variantId, 'base_profile_version_id' => $baseVersionId,
				'revision' => $version, 'overrides_json' => CanonicalJson::encodeObject($overrides),
				'overrides_checksum' => $compiled['overrides_checksum'], 'base_checksum' => $base['payload_checksum'],
				'payload_json' => CanonicalJson::encodeObject($compiled['profile']), 'payload_checksum' => $compiled['payload_checksum'],
				'created' => gmdate('Y-m-d H:i:s'), 'created_by' => $userId];
			$this->db->insertObject('#__joomleague_sport_variant_version', $record, 'id');
			return (int) $record->id;
		});
	}

	public function revision(int $id): array
	{
		if ($id < 1) throw new \InvalidArgumentException('Invalid sport variant revision ID.');
		$query = $this->db->getQuery(true)->select('r.*')->select('v.profile_id')
			->from($this->db->quoteName('#__joomleague_sport_variant_version', 'r'))
			->join('INNER', $this->db->quoteName('#__joomleague_sport_variant', 'v') . ' ON v.id = r.variant_id')
			->where('r.id = :id')->bind(':id', $id, ParameterType::INTEGER);
		$record = $this->db->setQuery($query)->loadAssoc();
		if (!$record) throw new \InvalidArgumentException('Sport variant revision does not exist.');
		$payload = json_decode($record['payload_json'], true, 512, JSON_THROW_ON_ERROR);
		$overrides = json_decode($record['overrides_json'], true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($payload) || !is_array($overrides)
			|| !hash_equals($record['payload_checksum'], CanonicalJson::checksum($payload))
			|| !hash_equals($record['overrides_checksum'], (new ProjectRuleValidator())->checksum($overrides))) {
			throw new \UnexpectedValueException('Sport variant revision checksum does not match.');
		}
		(new SportProfileSchemaValidator())->validate($payload);
		$baseId = (int) $record['base_profile_version_id'];
		$query = $this->db->getQuery(true)->select(['profile_id', 'payload_json', 'payload_checksum'])
			->from($this->db->quoteName('#__joomleague_sport_profile_version'))
			->where('id = :id')->bind(':id', $baseId, ParameterType::INTEGER);
		$base = $this->db->setQuery($query)->loadAssoc();
		if (!$base || (int) $base['profile_id'] !== (int) $record['profile_id']
			|| !hash_equals($record['base_checksum'], $base['payload_checksum'])) {
			throw new \UnexpectedValueException('Sport variant base binding does not match.');
		}
		$basePayload = json_decode($base['payload_json'], true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($basePayload) || !hash_equals($record['base_checksum'], hash('sha256', $base['payload_json']))) {
			throw new \UnexpectedValueException('Sport variant base checksum does not match.');
		}
		$compiled = (new SportVariantCompiler())->compile($basePayload, $overrides);
		if (!hash_equals($record['payload_checksum'], $compiled['payload_checksum'])) {
			throw new \UnexpectedValueException('Sport variant does not reproduce its stored contract.');
		}
		$record['profile'] = $payload;
		$record['overrides'] = $overrides;
		return $record;
	}

	public function options(): array
	{
		$query = $this->db->getQuery(true)->select(['r.id', 'r.revision', 'v.name', 'p.name_key'])
			->from($this->db->quoteName('#__joomleague_sport_variant_version', 'r'))
			->join('INNER', $this->db->quoteName('#__joomleague_sport_variant', 'v') . ' ON v.id = r.variant_id')
			->join('INNER', $this->db->quoteName('#__joomleague_sport_profile', 'p') . ' ON p.id = v.profile_id')
			->where('v.published = 1')->where('p.published = 1')->order(['p.code ASC', 'v.name ASC', 'r.id DESC']);
		return $this->db->setQuery($query)->loadAssocList();
	}

	/** Materializes a pinned contract without making it the family's latest base. */
	public function materialize(int $revisionId, int $profileId, int $actorId): int
	{
		return $this->write(function () use ($revisionId, $profileId, $actorId): int {
			$revision = $this->revision($revisionId);
			$variantId = (int) $revision['variant_id'];
			$variant = $this->db->setQuery('SELECT * FROM ' . $this->db->quoteName('#__joomleague_sport_variant') . ' WHERE id = ' . $variantId . ' FOR UPDATE')->loadAssoc();
			$locked = $this->db->setQuery('SELECT * FROM ' . $this->db->quoteName('#__joomleague_sport_variant_version') . ' WHERE id = ' . $revisionId . ' FOR UPDATE')->loadAssoc();
			if (!$variant || !$locked || (int) $variant['published'] !== 1 || (int) $variant['profile_id'] !== $profileId) throw new \InvalidArgumentException('Variant is unavailable for this sport.');
			if ((int) $this->db->setQuery('SELECT published FROM ' . $this->db->quoteName('#__joomleague_sport_profile') . ' WHERE id = ' . $profileId)->loadResult() !== 1) throw new \InvalidArgumentException('Sport profile is unpublished.');
			$revision = $this->revision($revisionId);
			$payload = $revision['profile'];
			// Identity prevents unrelated variants from sharing presentation snapshots.
			$payload['variant'] = ['uuid' => $variant['uuid'], 'revision' => $revision['revision'], 'base_checksum' => $revision['base_checksum']];
			$json = CanonicalJson::encodeObject($payload);
			$checksum = hash('sha256', $json);
			if ((int) ($locked['compiled_profile_version_id'] ?? 0) > 0) {
				$compiledId = (int) $locked['compiled_profile_version_id'];
				$record = $this->db->setQuery('SELECT profile_id, payload_json, payload_checksum FROM ' . $this->db->quoteName('#__joomleague_sport_profile_version') . ' WHERE id = ' . $compiledId)->loadAssoc();
				if (!$record || (int) $record['profile_id'] !== $profileId || !hash_equals($checksum, $record['payload_checksum']) || $record['payload_json'] !== $json) throw new \UnexpectedValueException('Pinned variant contract is inconsistent.');
				return $compiledId;
			}
			$compiled = (object) ['profile_id' => $profileId, 'schema_version' => $payload['schema_version'],
				'profile_version' => 'variant-' . substr($checksum, 0, 32), 'payload_json' => $json,
				'payload_checksum' => $checksum, 'source' => 'variant', 'state' => 'variant', 'created' => gmdate('Y-m-d H:i:s')];
			$this->db->insertObject('#__joomleague_sport_profile_version', $compiled, 'id');
			$compiledId = (int) $compiled->id;
			$baseId = (int) $revision['base_profile_version_id'];
			$query = $this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__joomleague_profile_template_config'))
				->where('profile_version_id = :base')->bind(':base', $baseId, ParameterType::INTEGER);
			foreach ($this->db->setQuery($query)->loadObjectList() as $template) {
				unset($template->id);
				$template->profile_version_id = $compiledId;
				$template->created = gmdate('Y-m-d H:i:s');
				$template->created_by = $actorId;
				$template->checked_out = null;
				$template->checked_out_time = null;
				$this->db->insertObject('#__joomleague_profile_template_config', $template, 'id');
			}
			$query = $this->db->getQuery(true)->update($this->db->quoteName('#__joomleague_sport_variant_version'))
				->set('compiled_profile_version_id = :compiled')->where('id = :id')
				->bind(':compiled', $compiledId, ParameterType::INTEGER)->bind(':id', $revisionId, ParameterType::INTEGER);
			$this->db->setQuery($query)->execute();
			return (int) $compiledId;
		});
	}

	private function write(callable $operation): int
	{
		$this->db->transactionStart();
		try {
			$id = $operation();
			$this->db->transactionCommit();
			return $id;
		} catch (\Throwable $error) {
			$this->db->transactionRollback();
			throw $error;
		}
	}
}
