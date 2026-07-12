<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

abstract class EntityFormController extends FormController
{
	protected function allowEdit($data = [], $key = 'id'): bool
	{
		$id = (int) ($data[$key] ?? 0);

		if ($id < 1) {
			return false;
		}

		return $this->app->getIdentity()->authorise('core.edit', $this->getAssetContext($id));
	}

	protected function allowSave($data, $key = 'id'): bool
	{
		$id = (int) ($data[$key] ?? 0);

		return $id > 0 ? $this->allowEdit($data, $key) : $this->allowAdd($data);
	}

	private function getAssetContext(int $id): string
	{
		$class = static::class;
		$short = substr($class, strrpos($class, '\\') + 1);
		$section = strtolower(preg_replace('/Controller$/', '', $short));

		return 'com_joomleague.' . $section . '.' . $id;
	}
}
