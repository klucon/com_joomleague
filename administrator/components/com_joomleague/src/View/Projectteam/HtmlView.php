<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Projectteam;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_PROJECTTEAM_NEW',
			'edit' => 'COM_JOOMLEAGUE_PROJECTTEAM_EDIT',
			'icon' => 'users',
			'singular' => 'projectteam',
			'details' => 'COM_JOOMLEAGUE_FIELDSET_DETAILS',
			'main' => ['project_id', 'team_id', 'division_id', 'standard_playground', 'start_points', 'is_in_score', 'use_finally', 'points_finally', 'neg_points_finally', 'reason', 'info', 'notes', 'extended'],
			'side' => ['media' => 'COM_JOOMLEAGUE_FIELDSET_MEDIA'],
			'publishing' => ['ordering'],
		];
	}
}
