<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Demodata;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string, string> */
	public array $profiles = [];

	/** @var array{enabled: bool, has_runtime_data: bool, confirmation: string} */
	public array $status = [];

	public function display($tpl = null): void
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_joomleague')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$this->profiles = $this->getModel()->getProfiles();
		$this->status = $this->getModel()->getStatus();
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_DEMODATA_TITLE'), 'database');
		ToolbarHelper::link('index.php?option=com_joomleague&view=tools', 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
