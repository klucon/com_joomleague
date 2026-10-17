<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Clubs;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

final class HtmlView extends BaseHtmlView
{
	/** @var list<object> */
	public array $clubs = [];

	/** @var list<object> */
	public array $countries = [];

	public Pagination $pagination;

	public string $search = '';

	public string $countryCode = '';

	public function display($tpl = null): void
	{
		$model = $this->getModel();
		$clubs = $model->getItems();
		$this->clubs = is_array($clubs) ? $clubs : [];
		foreach ($this->clubs as $club) $club->logo = \Joomleague\Component\Joomleague\Site\Service\MediaResolver::image('club', $club->logo);
		$this->countries = $model->getCountries();
		$this->pagination = $model->getPagination();
		$this->search = (string) $model->getState('filter.search');
		$this->countryCode = (string) $model->getState('filter.country_code');
		$start = (int) $model->getState('list.start', 0);
		$limit = max(1, (int) $model->getState('list.limit', 20));
		$page = intdiv($start, $limit) + 1;
		$title = $page > 1 ? Text::sprintf('COM_JOOMLEAGUE_SEO_PAGINATED_TITLE', Text::_('COM_JOOMLEAGUE_CLUBS_VIEW_TITLE'), $page) : Text::_('COM_JOOMLEAGUE_CLUBS_VIEW_TITLE');
		$route = 'index.php?option=com_joomleague&view=clubs';
		if ($this->countryCode !== '') $route .= '&filter_country_code=' . rawurlencode($this->countryCode);
		if ($start > 0) $route .= '&start=' . $start . '&limit=' . $limit;
		$seo = new SeoMetadata();
		$seo->apply($this->getDocument(), $title, Route::_($route, false, Route::TLS_IGNORE, true), Text::_('COM_JOOMLEAGUE_CLUBS_META_DESCRIPTION'));
		if ($this->search !== '') $seo->noIndex($this->getDocument());
		parent::display($tpl);
	}
}
