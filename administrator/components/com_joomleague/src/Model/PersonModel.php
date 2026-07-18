<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

final class PersonModel extends EntityAdminModel
{
	protected string $entityName = 'person';

	protected function prepareTable($table): void
	{
		foreach (['firstname', 'lastname', 'nickname', 'knvbnr', 'phone', 'mobile', 'email', 'website', 'address', 'zipcode', 'location', 'state', 'picture', 'info', 'notes'] as $field) {
			$table->$field = trim((string) $table->$field);
		}

		foreach (['birthday', 'deathday'] as $field) {
			$table->$field = trim((string) $table->$field) ?: null;
		}

		foreach (['height', 'weight'] as $field) {
			$value = trim((string) ($table->$field ?? ''));
			$table->$field = $value === '' ? null : (int) $value;
		}

		$table->country = trim((string) $table->country) ?: null;
		$table->address_country = trim((string) $table->address_country) ?: null;
		$table->contact_id = (int) $table->contact_id ?: null;
		$table->position_id = (int) $table->position_id ?: null;

		parent::prepareTable($table);
	}
}
