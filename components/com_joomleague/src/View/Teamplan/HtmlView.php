<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Teamplan;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    /** @var array<string,mixed> */
    public array $plan = [];

    public function display($tpl = null): void
    {
        $this->plan = $this->getModel()->getPlan();

        $title = isset($this->plan['entry'])
            ? (string) $this->plan['entry']->display_name
            : Text::_('COM_JOOMLEAGUE_TEAMPLAN_VIEW_TITLE');

        $this->getDocument()->setTitle($title);

        parent::display($tpl);
    }
}
