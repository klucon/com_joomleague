<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Sportstype;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_SPORTSTYPE_NEW',
			'edit' => 'COM_JOOMLEAGUE_SPORTSTYPE_EDIT',
			'icon' => 'grid-2',
			'singular' => 'sportstype',
			'details' => 'COM_JOOMLEAGUE_SPORTSTYPE_DETAILS',
			'main' => ['name', 'periods', 'icon'],
			'side' => [],
			'publishing' => ['published', 'ordering', 'id'],
		];
	}
}
