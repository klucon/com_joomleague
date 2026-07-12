<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Racecategory;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_RACECATEGORY_NEW',
			'edit' => 'COM_JOOMLEAGUE_RACECATEGORY_EDIT',
			'icon' => 'tags',
			'singular' => 'racecategory',
			'details' => 'COM_JOOMLEAGUE_FIELDSET_DETAILS',
			'main' => ['project_id', 'name', 'alias', 'sex', 'age_min', 'age_max'],
			'side' => [],
			'publishing' => ['published', 'ordering'],
		];
	}
}
