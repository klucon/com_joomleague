<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class EntryModelValidator
{
	private const ALLOWED_KINDS = ['team', 'person', 'group'];

	/** @param array<string,mixed> $profile */
	public function validate(array $profile): void
	{
		$model = $profile['entry_model'] ?? null;

		if (!is_array($model) || array_is_list($model)) {
			throw new \UnexpectedValueException('Sport profile entry_model must be an object.');
		}

		$kinds = $model['allowed_kinds'] ?? null;

		if (!is_array($kinds) || $kinds === [] || !array_is_list($kinds)) {
			throw new \UnexpectedValueException('Sport profile allowed entry kinds must be a non-empty list.');
		}

		foreach ($kinds as $kind) {
			if (!is_string($kind) || !in_array($kind, self::ALLOWED_KINDS, true)) {
				throw new \UnexpectedValueException('Sport profile contains an unsupported entry kind.');
			}
		}

		if (count($kinds) !== count(array_unique($kinds))) {
			throw new \UnexpectedValueException('Sport profile entry kinds must be unique.');
		}

		$defaultKind = $model['default_kind'] ?? null;

		if (!is_string($defaultKind) || !in_array($defaultKind, $kinds, true)) {
			throw new \UnexpectedValueException('Sport profile default entry kind must be allowed.');
		}

		if (!is_bool($model['members_supported'] ?? null)) {
			throw new \UnexpectedValueException('Sport profile members_supported must be boolean.');
		}

		$memberTypes = $model['member_person_types'] ?? null;

		if (!is_array($memberTypes) || !array_is_list($memberTypes)) {
			throw new \UnexpectedValueException('Sport profile member person types must be a list.');
		}

		if (!$model['members_supported'] && $memberTypes !== []) {
			throw new \UnexpectedValueException('A profile without member support cannot define member person types.');
		}

		if (in_array('group', $kinds, true) && !$model['members_supported']) {
			throw new \UnexpectedValueException('Group entries require member support.');
		}

		$positionPersonTypes = [];

		foreach ($profile['positions'] ?? [] as $position) {
			if (is_array($position) && is_string($position['person_type'] ?? null)) {
				$positionPersonTypes[] = $position['person_type'];
			}
		}

		foreach ($memberTypes as $personType) {
			if (!is_string($personType) || preg_match('/^[a-z][a-z0-9_]*$/', $personType) !== 1 || !in_array($personType, $positionPersonTypes, true)) {
				throw new \UnexpectedValueException('Sport profile member person type is not defined by its positions.');
			}
		}

		if (count($memberTypes) !== count(array_unique($memberTypes))) {
			throw new \UnexpectedValueException('Sport profile member person types must be unique.');
		}
	}
}
