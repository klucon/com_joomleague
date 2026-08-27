<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Eventreport;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $eventReport = [];

	public function display($tpl = null): void
	{
		$this->eventReport = $this->getModel()->getItem();
		$title = isset($this->eventReport['item'])
			? (string) $this->eventReport['item']->project_name
			: Text::_('COM_JOOMLEAGUE_EVENTREPORT_VIEW_TITLE');
		$this->getDocument()->setTitle($title);
		parent::display($tpl);
	}
}
