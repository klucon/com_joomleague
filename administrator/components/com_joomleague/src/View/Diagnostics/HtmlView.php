<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Diagnostics;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $report = [];
	public function display($tpl = null): void
	{
		$this->report = $this->getModel()->getReport();
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_DIAGNOSTICS_TITLE'), 'health');
		ToolbarHelper::link('index.php?option=com_joomleague&view=diagnostics', 'COM_JOOMLEAGUE_DIAGNOSTICS_REFRESH', 'refresh');
		ToolbarHelper::link('index.php?option=com_joomleague&view=tools', 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
