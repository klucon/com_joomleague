<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Projects;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

final class HtmlView extends BaseHtmlView
{
	/** @var list<object> */
	public array $projects = [];

	public function display($tpl = null): void
	{
		$this->projects = $this->getModel()->getProjects();
		(new SeoMetadata())->apply($this->getDocument(), Text::_('COM_JOOMLEAGUE_PROJECTS_VIEW_TITLE'), Route::_('index.php?option=com_joomleague&view=projects', false, Route::TLS_IGNORE, true), Text::_('COM_JOOMLEAGUE_PROJECTS_META_DESCRIPTION'));
		parent::display($tpl);
	}
}
