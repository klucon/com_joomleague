<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Languages;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $languages = [];
	public array $summary = [];

	public function display($tpl = null): void
	{
		$this->languages = $this->getModel()->getLanguages();
		$this->summary = $this->getModel()->getSummary();

		$this->getDocument()
			->getWebAssetManager()
			->registerAndUseStyle(
				'com_joomleague.dashboard',
				'com_joomleague/css/dashboard.css',
				['version' => '0.5.2']
			);

		ToolbarHelper::title('JoomLeague: ' . Text::_('COM_JOOMLEAGUE_LANGUAGES_TITLE'), 'comments');
		ToolbarHelper::link('index.php?option=com_joomleague&view=dashboard', 'COM_JOOMLEAGUE_MENU_DASHBOARD', 'arrow-left');

		parent::display($tpl);
	}
}
