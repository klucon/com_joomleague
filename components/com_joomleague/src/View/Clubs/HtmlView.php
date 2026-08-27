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
		$this->countries = $model->getCountries();
		$this->pagination = $model->getPagination();
		$this->search = (string) $model->getState('filter.search');
		$this->countryCode = (string) $model->getState('filter.country_code');
		$this->getDocument()->setTitle(Text::_('COM_JOOMLEAGUE_CLUBS_VIEW_TITLE'));
		parent::display($tpl);
	}
}
