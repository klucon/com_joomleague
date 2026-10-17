<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Nextmatch;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $event = [];

	public function display($tpl = null): void
	{
		$this->event = $this->getModel()->getEvent();
		PublicViewGuard::requireItem($this->event, 'project');
		$title = Text::sprintf('COM_JOOMLEAGUE_NEXTMATCH_PAGE_TITLE', $this->event['project']->name);
		(new SeoMetadata())->applyView($this->getDocument(), 'nextmatch', ['project_id' => (int) $this->event['project']->id], $title, Text::sprintf('COM_JOOMLEAGUE_SEO_PROJECT_DESCRIPTION', Text::_('COM_JOOMLEAGUE_NEXTMATCH_VIEW_TITLE'), $this->event['project']->name));
		parent::display($tpl);
	}
}
