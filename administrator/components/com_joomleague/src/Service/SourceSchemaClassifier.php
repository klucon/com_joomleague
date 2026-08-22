<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class SourceSchemaClassifier
{
	/**
	 * @param array<string, list<string>> $schema Unprefixed table names mapped to column names.
	 * @return array{classification: string, confidence: string, evidence: list<string>, candidates: list<string>}
	 */
	public function classify(array $schema): array
	{
		$normalised = [];

		foreach ($schema as $table => $columns) {
			$normalised[strtolower($table)] = array_values(array_unique(array_map('strtolower', $columns)));
		}

		$canonicalEvidence = $this->canonicalEvidence($normalised);
		$legacyEvidence = $this->legacyEvidence($normalised);
		$hasCanonical = count($canonicalEvidence) >= 3;
		$hasLegacy = count($legacyEvidence) >= 3;

		if ($hasCanonical && $hasLegacy) {
			return $this->result('mixed', 'high', [...$canonicalEvidence, ...$legacyEvidence], $this->legacyCandidates($normalised));
		}

		if ($hasCanonical) {
			return $this->result('canonical', count($canonicalEvidence) >= 5 ? 'high' : 'medium', $canonicalEvidence, ['joomleague_6_2_canonical']);
		}

		if ($hasLegacy) {
			return $this->result('legacy', count($legacyEvidence) >= 5 ? 'high' : 'medium', $legacyEvidence, $this->legacyCandidates($normalised));
		}

		return $this->result('unknown', 'low', [...$canonicalEvidence, ...$legacyEvidence], []);
	}

	/** @param array<string, list<string>> $schema @return list<string> */
	private function canonicalEvidence(array $schema): array
	{
		$evidence = [];

		foreach ([
			['joomleague_project', 'uuid'],
			['joomleague_project', 'competition_id'],
			['joomleague_project', 'profile_version_id'],
			['joomleague_sport_profile_version', 'payload_checksum'],
			['joomleague_project_entry', 'entry_kind'],
		] as [$table, $column]) {
			if ($this->hasColumn($schema, $table, $column)) {
				$evidence[] = $table . '.' . $column;
			}
		}

		return $evidence;
	}

	/** @param array<string, list<string>> $schema @return list<string> */
	private function legacyEvidence(array $schema): array
	{
		$evidence = [];

		foreach ([
			['joomleague_project', 'league_id'],
			['joomleague_project', 'game_regular_time'],
			['joomleague_project', 'points_after_regular_time'],
			['joomleague_project_team', 'team_id'],
			['joomleague_team_player', 'projectteam_id'],
		] as [$table, $column]) {
			if ($this->hasColumn($schema, $table, $column)) {
				$evidence[] = $table . '.' . $column;
			}
		}

		foreach (['joomleague_league', 'joomleague_playground', 'joomleague_sports_type'] as $table) {
			if (isset($schema[$table])) {
				$evidence[] = $table;
			}
		}

		return $evidence;
	}

	/** @param array<string, list<string>> $schema @return list<string> */
	private function legacyCandidates(array $schema): array
	{
		$candidates = [];

		if ($this->hasColumn($schema, 'joomleague_project', 'tmp_old_pid')
			|| $this->hasColumn($schema, 'joomleague_team_player', 'tmp_old_project_id')) {
			$candidates[] = 'joomleague_0_93_upgrade_artifacts';
		}

		if ($this->hasColumn($schema, 'joomleague_project', 'asset_id')) {
			$candidates[] = 'joomleague_1_6_or_newer';
		}

		if ($this->hasColumn($schema, 'joomleague_project', 'is_utc_converted')) {
			$candidates[] = 'joomleague_2_or_newer';
		}

		if (isset($schema['joomleague_version'])) {
			$candidates[] = 'version_table_requires_value_inspection';
		}

		return array_values(array_unique($candidates ?: ['legacy_version_requires_data_inspection']));
	}

	/** @param array<string, list<string>> $schema */
	private function hasColumn(array $schema, string $table, string $column): bool
	{
		return isset($schema[$table]) && in_array($column, $schema[$table], true);
	}

	/** @param list<string> $evidence @param list<string> $candidates */
	private function result(string $classification, string $confidence, array $evidence, array $candidates): array
	{
		return [
			'classification' => $classification,
			'confidence' => $confidence,
			'evidence' => array_values(array_unique($evidence)),
			'candidates' => array_values(array_unique($candidates)),
		];
	}
}
