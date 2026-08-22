<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Templates;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	/** @var list<object> */
	public array $items = [];

	/** @var array{profiles: int, templates: int, overrides: int, definitions: int} */
	public array $summary = [];

	public function display($tpl = null): void
	{
		$model = $this->getModel();
		$this->items = $model->getItems();
		$this->summary = $model->getSummary();
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_TEMPLATES_TITLE'), 'palette');
		parent::display($tpl);
	}
}
