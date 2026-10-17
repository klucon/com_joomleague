<?php

declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\View\Sportcategories;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $items = [];
	public array $draft = [];

	public function display($tpl = null): void
	{
		$this->items = $this->getModel()->getItems();
		$app = Factory::getApplication();
		$this->draft = $app->getUserState('com_joomleague.sportcategory.draft', []);
		$app->setUserState('com_joomleague.sportcategory.draft', null);
		$id = $app->getInput()->getInt('id');
		if ($this->draft === [] && $id > 0) {
			foreach ($this->items as $item) if ((int) $item->id === $id) $this->draft = (array) $item;
			if ($this->draft === []) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_ERROR_MISSING'), 404);
		}
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_SPORTCATEGORIES_TITLE'), 'folder');
		parent::display($tpl);
	}
}
