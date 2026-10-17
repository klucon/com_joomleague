<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Sportprofiles;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	/** @var list<object> */
	public array $items = [];
	public array $categories = [];
	public array $assignments = [];

	public function display($tpl = null): void
	{
		$this->items = $this->getModel()->getItems();
		$this->categories = $this->getModel()->getCategories();
		$this->assignments = $this->getModel()->getCategoryAssignments();
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_SPORTPROFILES_TITLE'), 'puzzle-piece');
		ToolbarHelper::custom('sportprofiles.synchronise', 'refresh', '', 'COM_JOOMLEAGUE_SPORTPROFILES_SYNC_ACTION', false);
		parent::display($tpl);
	}
}
