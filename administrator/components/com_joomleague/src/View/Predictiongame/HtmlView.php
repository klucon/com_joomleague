<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Predictiongame;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_PREDICTIONGAME_NEW',
			'edit' => 'COM_JOOMLEAGUE_PREDICTIONGAME_EDIT',
			'icon' => 'star',
			'singular' => 'predictiongame',
			'details' => 'COM_JOOMLEAGUE_PREDICTIONGAME_FIELDSET_DETAILS',
			'main' => ['project_id', 'name', 'deadline_minutes'],
			'side' => ['rules' => 'COM_JOOMLEAGUE_PREDICTIONGAME_FIELDSET_RULES', 'options' => 'COM_JOOMLEAGUE_PREDICTIONGAME_FIELDSET_OPTIONS'],
			'publishing' => ['published'],
		];
	}
}
