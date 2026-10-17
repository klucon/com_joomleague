<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Clubpeople;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $people = [];

	public function display($tpl = null): void
	{
		$this->people = $this->getModel()->getPeople();
		PublicViewGuard::requireItem($this->people, 'club');
		$title = Text::sprintf('COM_JOOMLEAGUE_CLUBPEOPLE_PAGE_TITLE', $this->people['club']->name);
		$state = $this->people['pagination_state'];
		$page = intdiv((int) $state['start'], max(1, (int) $state['limit'])) + 1;
		if ($page > 1) $title = Text::sprintf('COM_JOOMLEAGUE_SEO_PAGINATED_TITLE', $title, $page);
		$input = Factory::getApplication()->getInput();
		$seo = new SeoMetadata();
		$seo->applyView($this->getDocument(), 'clubpeople', [
			'club_id' => (int) $this->people['club']->id,
			'filter_project_id' => $input->getInt('filter_project_id', 0),
			'filter_season_id' => $input->getInt('filter_season_id', 0),
			'filter_project_type' => $input->getCmd('filter_project_type', ''),
			'filter_person_type' => $input->getCmd('filter_person_type', ''),
			'start' => (int) $state['start'],
			'limit' => (int) $state['start'] > 0 ? (int) $state['limit'] : 0,
		], $title, Text::sprintf('COM_JOOMLEAGUE_SEO_ENTITY_DESCRIPTION', Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_VIEW_DEFAULT_TITLE'), $this->people['club']->name));
		if (trim($input->getString('filter_search', '')) !== '') $seo->noIndex($this->getDocument());
		parent::display($tpl);
	}
}
