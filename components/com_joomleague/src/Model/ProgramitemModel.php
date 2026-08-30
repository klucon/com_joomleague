<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/** @deprecated Use EventreportModel and the event_id request parameter. */
final class ProgramitemModel extends EventreportModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		parent::populateState($ordering, $direction);
		$legacyId = Factory::getApplication()->getInput()->getInt('match_id', 0);

		if ($legacyId > 0) {
			$this->setState('event_id', $legacyId);
		}
	}
}
