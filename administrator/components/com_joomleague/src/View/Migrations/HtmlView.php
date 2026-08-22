<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Migrations;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	/** @var list<object> */
	public array $items = [];
	/** @var array<string, mixed> */
	public array $sourceInventory = [];

	public function display($tpl = null): void
	{
		$this->items = $this->getModel()->getItems();
		$this->sourceInventory = $this->getModel()->getSourceInventory();
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_MIGRATIONS_TITLE'), 'refresh');
		parent::display($tpl);
	}
}
