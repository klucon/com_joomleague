<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Statranking;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */ public array $ranking = [];
	public function display($tpl = null): void
	{
		$this->ranking = $this->getModel()->getRanking();
		$title = isset($this->ranking['project']) ? Text::sprintf('COM_JOOMLEAGUE_STATRANKING_PAGE_TITLE', $this->ranking['project']->name) : Text::_('COM_JOOMLEAGUE_STATRANKING_VIEW_TITLE');
		$this->getDocument()->setTitle($title);
		parent::display($tpl);
	}
}
