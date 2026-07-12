<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Sqlimport;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public function display($tpl = null): void
	{
		ToolbarHelper::title('JoomLeague: ' . Text::_('COM_JOOMLEAGUE_SQLIMPORT_TITLE'), 'database');
		ToolbarHelper::link('index.php?option=com_joomleague&view=tools', 'COM_JOOMLEAGUE_MENU_TOOLS', 'arrow-left');

		parent::display($tpl);
	}
}
