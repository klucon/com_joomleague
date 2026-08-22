<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\View\Stageprogression;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends BaseHtmlView
{
	public array $preview = [];
	public bool $canApply = false;
	public function display($tpl = null): void
	{
		$id = Factory::getApplication()->getInput()->getInt('transition_id');
		$this->preview = $this->getModel()->preview($id);
		$this->canApply = $this->preview['executable'] && Factory::getApplication()->getIdentity()->authorise('joomleague.project.run.transitions', 'com_joomleague.project.' . (int) $this->preview['transition']->project_id);
		ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_STAGE_PROGRESSION_TITLE', $this->preview['transition']->name), 'shuffle');
		if ($this->canApply) ToolbarHelper::save('stageprogression.apply', 'COM_JOOMLEAGUE_STAGE_PROGRESSION_APPLY');
		ToolbarHelper::link('index.php?option=com_joomleague&view=stagetransitions&project_id=' . (int) $this->preview['transition']->project_id, 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
