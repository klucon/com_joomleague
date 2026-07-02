<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Tools;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public function display($tpl = null): void
	{
		ToolbarHelper::title('JoomLeague: ' . Text::_('COM_JOOMLEAGUE_TOOLS_TITLE'), 'wrench');
		ToolbarHelper::link('index.php?option=com_joomleague&view=dashboard', 'COM_JOOMLEAGUE_MENU_DASHBOARD', 'arrow-left');
		ToolbarHelper::preferences('com_joomleague');
		parent::display($tpl);
	}
}
