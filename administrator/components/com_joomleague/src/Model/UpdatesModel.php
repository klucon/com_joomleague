<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class UpdatesModel extends BaseDatabaseModel
{
	public function getSqlUpdates(): array
	{
		$path = JPATH_COMPONENT_ADMINISTRATOR . '/sql/updates/mysql';
		$files = glob($path . '/*.sql') ?: [];
		sort($files, SORT_NATURAL);

		return array_map(static fn(string $file): object => (object) [
			'name' => basename($file),
			'size' => filesize($file) ?: 0,
			'modified' => date('Y-m-d H:i:s', filemtime($file) ?: time()),
		], $files);
	}
}
