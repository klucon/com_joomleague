<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Project;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $project = [];

	public function display($tpl = null): void
	{
		$this->project = $this->getModel()->getProject();
		$title = isset($this->project['project'])
			? (string) $this->project['project']->name
			: Text::_('COM_JOOMLEAGUE_PROJECT_VIEW_TITLE');
		$this->getDocument()->setTitle($title);
		parent::display($tpl);
	}
}
