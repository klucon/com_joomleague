<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Databasetools;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $tables = [];

	public function display($tpl = null): void
	{
		$this->tables = $this->getModel()->getTables();
		ToolbarHelper::title('JoomLeague: ' . \Joomla\CMS\Language\Text::_('COM_JOOMLEAGUE_DBTOOLS_TITLE'), 'database');
		ToolbarHelper::link('index.php?option=com_joomleague&view=tools', 'COM_JOOMLEAGUE_TOOLS_BACK', 'arrow-left');
		parent::display($tpl);
	}
}
