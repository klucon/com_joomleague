<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Section;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use RuntimeException;

class HtmlView extends BaseHtmlView
{
	public array $section = [];

	public function display($tpl = null): void
	{
		$section = $this->getModel()->getSection($this->getName());

		if ($section === null) {
			throw new RuntimeException(Text::_('COM_JOOMLEAGUE_SECTION_NOT_FOUND'), 404);
		}

		$this->section = $section;
		$this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/section');
		$this->getDocument()
			->getWebAssetManager()
			->registerAndUseStyle(
				'com_joomleague.dashboard',
				'com_joomleague/css/dashboard.css',
				['version' => '0.5.2']
			);
		ToolbarHelper::title(Text::_($section['title']), $section['icon']);

		parent::display($tpl);
	}
}
