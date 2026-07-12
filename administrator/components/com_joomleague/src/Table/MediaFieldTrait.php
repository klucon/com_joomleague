<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

\defined('_JEXEC') or die;

trait MediaFieldTrait
{
	private function normalizeMediaField(string $field): void
	{
		if (!property_exists($this, $field)) {
			return;
		}

		$value = $this->{$field};

		if (is_array($value)) {
			$value = $value['imagefile'] ?? $value['url'] ?? reset($value) ?: '';
		}

		$value = trim((string) $value);

		if ($value !== '' && str_contains($value, '#')) {
			$value = strtok($value, '#') ?: '';
		}

		$this->{$field} = trim($value);
	}

	private function normalizeMediaFields(array $fields): void
	{
		foreach ($fields as $field) {
			$this->normalizeMediaField($field);
		}
	}
}
