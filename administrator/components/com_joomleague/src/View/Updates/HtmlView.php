<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Updates;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $updates = [];

	public function display($tpl = null): void
	{
		$this->updates = $this->getModel()->getSqlUpdates();
		ToolbarHelper::title('JoomLeague: ' . Text::_('COM_JOOMLEAGUE_UPDATES_TITLE'), 'refresh');
		ToolbarHelper::link('index.php?option=com_joomleague&view=tools', 'COM_JOOMLEAGUE_TOOLS_BACK', 'arrow-left');
		parent::display($tpl);
	}
}
