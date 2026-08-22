<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Databasetools;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $items = [];
	public function display($tpl = null): void
	{
		$this->items = $this->getModel()->getTables();
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_DATABASETOOLS_TITLE'), 'database');
		ToolbarHelper::custom('databasetools.export', 'download', '', 'COM_JOOMLEAGUE_DATABASETOOLS_EXPORT_SELECTED', false);
		if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_joomleague')) {
			ToolbarHelper::custom('databasetools.rebuildProjectAssets', 'refresh', '', 'COM_JOOMLEAGUE_DATABASETOOLS_REBUILD_ASSETS', false);
		}
		ToolbarHelper::link('index.php?option=com_joomleague&view=tools', 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
