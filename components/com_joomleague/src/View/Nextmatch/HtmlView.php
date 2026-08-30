<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Nextmatch;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $event = [];

	public function display($tpl = null): void
	{
		$this->event = $this->getModel()->getEvent();
		$this->getDocument()->setTitle(isset($this->event['item']) ? (string) $this->event['item']->project_name : Text::_('COM_JOOMLEAGUE_NEXTMATCH_VIEW_TITLE'));
		parent::display($tpl);
	}
}
