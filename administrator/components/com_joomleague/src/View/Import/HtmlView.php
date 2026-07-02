<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Import;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $targets = [];
	public array $columns = [];

	public function display($tpl = null): void
	{
		$model = $this->getModel();
		$this->targets = $model->getTargets();

		foreach (array_keys($this->targets) as $target) {
			$this->columns[$target] = $model->getColumns($target);
		}

		ToolbarHelper::title('JoomLeague: ' . Text::_('COM_JOOMLEAGUE_IMPORT_TITLE'), 'upload');
		ToolbarHelper::link('index.php?option=com_joomleague&view=tools', 'COM_JOOMLEAGUE_TOOLS_BACK', 'arrow-left');
		parent::display($tpl);
	}
}
