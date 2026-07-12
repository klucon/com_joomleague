<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Template;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_TEMPLATE_NEW',
			'edit' => 'COM_JOOMLEAGUE_TEMPLATE_EDIT',
			'icon' => 'palette',
			'singular' => 'template',
			'details' => 'COM_JOOMLEAGUE_TEMPLATE_FIELDSET_DETAILS',
			'main' => ['project_id', 'template', 'title'],
			'side' => ['display' => 'COM_JOOMLEAGUE_TEMPLATE_FIELDSET_DISPLAY'],
			'publishing' => ['published'],
		];
	}
}
