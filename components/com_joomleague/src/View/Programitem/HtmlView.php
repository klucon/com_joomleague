<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Programitem;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $programItem = [];

	public function display($tpl = null): void
	{
		$this->programItem = $this->getModel()->getItem();
		$title = isset($this->programItem['item'])
			? (string) $this->programItem['item']->project_name
			: Text::_('COM_JOOMLEAGUE_PROGRAMITEM_VIEW_TITLE');
		$this->getDocument()->setTitle($title);
		parent::display($tpl);
	}
}
