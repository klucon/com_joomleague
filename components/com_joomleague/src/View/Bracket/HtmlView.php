<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Bracket;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;

final class HtmlView extends BaseHtmlView
{
    /** @var array<string,mixed> */
    public array $bracket = [];

    public function display($tpl = null): void
    {
        $this->bracket = $this->getModel()->getBracket();
		PublicViewGuard::requireItem($this->bracket, 'project');

        $title = isset($this->bracket['project'])
            ? Text::sprintf('COM_JOOMLEAGUE_BRACKET_PAGE_TITLE', $this->bracket['project']->name)
            : Text::_('COM_JOOMLEAGUE_BRACKET_VIEW_TITLE');

		if (isset($this->bracket['project'])) {
			$project = $this->bracket['project'];
			$stageId = Factory::getApplication()->getInput()->getInt('stage_id', 0);
			$route = 'index.php?option=com_joomleague&view=bracket&project_id=' . (int) $project->id;
			if ($stageId > 0) $route .= '&stage_id=' . $stageId;
			(new SeoMetadata())->apply($this->getDocument(), $title, Route::_($route, false, Route::TLS_IGNORE, true), Text::sprintf('COM_JOOMLEAGUE_BRACKET_META_DESCRIPTION', $project->name));
		} else {
			$this->getDocument()->setTitle($title);
		}

        parent::display($tpl);
    }
}
