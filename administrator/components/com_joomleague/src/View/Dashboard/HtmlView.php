<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Dashboard;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomleague\Component\Joomleague\Administrator\Helper\LanguageStatusHelper;

final class HtmlView extends BaseHtmlView
{
	public array $sections = [];
	public int $totalItems = 0;
	public array $translationSummary = [];

	public function display($tpl = null): void
	{
		$this->sections = $this->getModel()->getSections();
		$this->totalItems = array_sum(array_column($this->sections, 'count'));
		$this->translationSummary = LanguageStatusHelper::getSummary();
		$this->getDocument()
			->getWebAssetManager()
			->registerAndUseStyle(
				'com_joomleague.dashboard',
				'com_joomleague/css/dashboard.css',
				['version' => '0.5.2']
			);
		ToolbarHelper::title('JoomLeague', 'joomla');

		if ($this->getCurrentUser()->authorise('core.admin', 'com_joomleague') || $this->getCurrentUser()->authorise('core.options', 'com_joomleague')) {
			ToolbarHelper::preferences('com_joomleague');
		}

		parent::display($tpl);
	}
}
