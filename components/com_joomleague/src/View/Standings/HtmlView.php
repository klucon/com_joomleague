<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Standings;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $standings = [];

	public function display($tpl = null): void
	{
		$this->standings = $this->getModel()->getStandings();

		$title = isset($this->standings['project'])
			? (string) $this->standings['project']->name
			: Text::_('COM_JOOMLEAGUE_STANDINGS_VIEW_TITLE');

		$this->getDocument()->setTitle($title);

		parent::display($tpl);
	}
}
