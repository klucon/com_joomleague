<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Clubrivals;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $rivals = [];
	public function display($tpl = null): void
	{
		$this->rivals = $this->getModel()->getRivals();
		PublicViewGuard::requireItem($this->rivals, 'club');
		$this->rivals['club']->logo = \Joomleague\Component\Joomleague\Site\Service\MediaResolver::image('club', $this->rivals['club']->logo);
		$title = Text::sprintf('COM_JOOMLEAGUE_CLUBRIVALS_PAGE_TITLE', $this->rivals['club']->name);
		$state = $this->rivals['pagination_state'];
		$page = intdiv((int) $state['start'], max(1, (int) $state['limit'])) + 1;
		if ($page > 1) $title = Text::sprintf('COM_JOOMLEAGUE_SEO_PAGINATED_TITLE', $title, $page);
		$input = Factory::getApplication()->getInput();
		$seo = new SeoMetadata();
		$seo->applyView($this->getDocument(), 'clubrivals', [
			'club_id' => (int) $this->rivals['club']->id,
			'filter_project_id' => $input->getInt('filter_project_id', 0),
			'filter_season_id' => $input->getInt('filter_season_id', 0),
			'filter_project_type' => $input->getCmd('filter_project_type', ''),
			'filter_opponent_club_id' => $input->getInt('filter_opponent_club_id', 0),
			'start' => (int) $state['start'],
			'limit' => (int) $state['start'] > 0 ? (int) $state['limit'] : 0,
		], $title, Text::sprintf('COM_JOOMLEAGUE_SEO_ENTITY_DESCRIPTION', Text::_('COM_JOOMLEAGUE_CLUBRIVALS_VIEW_DEFAULT_TITLE'), $this->rivals['club']->name));
		if (trim($input->getString('filter_search', '')) !== '') $seo->noIndex($this->getDocument());
		parent::display($tpl);
	}
}
