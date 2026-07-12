<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

\defined('_JEXEC') or die;

trait AssetTableTrait
{
	protected function _getAssetName(): string
	{
		return 'com_joomleague.' . $this->getJoomleagueAssetSection() . '.' . (int) $this->id;
	}

	protected function _getAssetTitle(): string
	{
		foreach (['name', 'title', 'match_number', 'short_name'] as $field) {
			if (property_exists($this, $field) && trim((string) $this->$field) !== '') {
				return trim((string) $this->$field);
			}
		}

		return $this->getJoomleagueAssetSection() . ' #' . (int) $this->id;
	}

	protected function _getAssetParentId(?\Joomla\CMS\Table\Table $table = null, $id = null): int
	{
		$asset = new \Joomla\CMS\Table\Asset($this->getDatabase(), $this->getDispatcher());

		return $asset->loadByName('com_joomleague') ? (int) $asset->id : parent::_getAssetParentId($table, $id);
	}

	private function getJoomleagueAssetSection(): string
	{
		$class = static::class;
		$short = substr($class, strrpos($class, '\\') + 1);

		return strtolower(preg_replace('/Table$/', '', $short));
	}
}
