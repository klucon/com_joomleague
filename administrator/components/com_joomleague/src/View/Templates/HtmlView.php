<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Templates;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	/** @var list<object> */
	public array $items = [];

	/** @var array{profiles: int, templates: int, overrides: int, definitions: int} */
	public array $summary = [];
	public bool $canEditProfiles = false;

	public function display($tpl = null): void
	{
		$model = $this->getModel();

		try {
			$this->items = $model->getItems();
			$this->summary = $model->getSummary();
		} catch (\Throwable $exception) {
			throw new GenericDataException(Text::_($exception->getMessage()), 500);
		}
		$this->canEditProfiles = Factory::getApplication()->getIdentity()->authorise('core.options', 'com_joomleague');
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_TEMPLATES_TITLE'), 'palette');
		parent::display($tpl);
	}
}
