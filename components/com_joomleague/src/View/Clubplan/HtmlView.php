<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Clubplan;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $plan = [];

	public function display($tpl = null): void
	{
		$this->plan = $this->getModel()->getPlan();
		$this->getDocument()->setTitle(isset($this->plan['club']) ? (string) $this->plan['club']->name : Text::_('COM_JOOMLEAGUE_CLUBPLAN_VIEW_TITLE'));
		parent::display($tpl);
	}
}
