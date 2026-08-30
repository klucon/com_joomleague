<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Participantstats;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */ public array $statistics = [];

	public function display($tpl = null): void
	{
		$this->statistics = $this->getModel()->getStatistics();
		$this->getDocument()->setTitle(isset($this->statistics['entry'])
			? Text::sprintf('COM_JOOMLEAGUE_PARTICIPANTSTATS_PAGE_TITLE', $this->statistics['entry']->display_name)
			: Text::_('COM_JOOMLEAGUE_PARTICIPANTSTATS_VIEW_TITLE'));
		parent::display($tpl);
	}
}
