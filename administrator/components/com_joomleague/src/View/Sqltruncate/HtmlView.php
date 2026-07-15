<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Sqltruncate;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $counts = ['user' => [], 'reference' => []];

	public function display($tpl = null): void
	{
		if (!$this->getCurrentUser()->authorise('core.admin', 'com_joomleague')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$this->counts = $this->getModel()->getCounts();

		ToolbarHelper::title('JoomLeague: ' . Text::_('COM_JOOMLEAGUE_SQLTRUNCATE_TITLE'), 'trash');
		ToolbarHelper::link('index.php?option=com_joomleague&view=tools', 'COM_JOOMLEAGUE_MENU_TOOLS', 'arrow-left');

		parent::display($tpl);
	}
}
