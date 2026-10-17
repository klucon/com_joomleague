<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Clubplan;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $plan = [];

	public function display($tpl = null): void
	{
		$this->plan = $this->getModel()->getPlan();
		PublicViewGuard::requireItem($this->plan, 'club');
		$title = Text::sprintf('COM_JOOMLEAGUE_CLUBPLAN_PAGE_TITLE', $this->plan['club']->name);
		$input = Factory::getApplication()->getInput();
		(new SeoMetadata())->applyView($this->getDocument(), 'clubplan', [
			'project_id' => (int) $this->plan['project_id'],
			'club_id' => (int) $this->plan['club']->id,
			'entry_id' => $input->getInt('entry_id', 0),
			'period' => $input->getCmd('period', ''),
		], $title, Text::sprintf('COM_JOOMLEAGUE_SEO_ENTITY_DESCRIPTION', Text::_('COM_JOOMLEAGUE_CLUBPLAN_VIEW_TITLE'), $this->plan['club']->name));
		parent::display($tpl);
	}
}
