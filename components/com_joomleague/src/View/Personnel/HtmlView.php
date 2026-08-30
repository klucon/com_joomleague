<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Personnel;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $personnel = [];

	public function display($tpl = null): void
	{
		$this->personnel = $this->getModel()->getPersonnel();
		$this->getDocument()->setTitle(isset($this->personnel['project'])
			? Text::sprintf('COM_JOOMLEAGUE_PERSONNEL_PAGE_TITLE', $this->personnel['project']->name)
			: Text::_('COM_JOOMLEAGUE_PERSONNEL_VIEW_TITLE'));
		parent::display($tpl);
	}
}
