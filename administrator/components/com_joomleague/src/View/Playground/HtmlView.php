<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Playground;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_PLAYGROUND_NEW',
			'edit' => 'COM_JOOMLEAGUE_PLAYGROUND_EDIT',
			'icon' => 'location',
			'singular' => 'playground',
			'details' => 'COM_JOOMLEAGUE_PLAYGROUND_DETAILS',
			'main' => ['name', 'short_name', 'alias', 'address', 'zipcode', 'city', 'country', 'max_visitors', 'website', 'club_id', 'picture', 'notes', 'extended'],
			'side' => [],
			'publishing' => ['ordering', 'id'],
		];
	}
}
