<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Tools;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public function display($tpl = null): void
	{
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_TOOLS_TITLE'), 'wrench');
		parent::display($tpl);
	}
}
