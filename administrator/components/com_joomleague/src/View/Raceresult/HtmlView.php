<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Raceresult;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_RACERESULT_NEW',
			'edit' => 'COM_JOOMLEAGUE_RACERESULT_EDIT',
			'icon' => 'chart',
			'singular' => 'raceresult',
			'details' => 'COM_JOOMLEAGUE_FIELDSET_DETAILS',
			'main' => ['project_id', 'round_id', 'participant_id', 'status', 'duration_text', 'duration_ms'],
			'side' => [
				'ranking' => 'COM_JOOMLEAGUE_RACERESULT_FIELDSET_RANKING',
				'timing' => 'COM_JOOMLEAGUE_RACERESULT_FIELDSET_TIMING',
			],
			'publishing' => ['published', 'ordering'],
		];
	}
}
