<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Dataimport;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Utility\Utility;

final class HtmlView extends BaseHtmlView
{
	public string $maxUploadSize;

	public function display($tpl = null): void
	{
		$this->maxUploadSize = HTMLHelper::_('number.bytes', Utility::getMaxUploadSize());
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_DATAIMPORT_TITLE'), 'upload');
		ToolbarHelper::custom('dataimport.import', 'upload', '', 'COM_JOOMLEAGUE_DATAIMPORT_ACTION', false);
		ToolbarHelper::link('index.php?option=com_joomleague&view=tools', 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
