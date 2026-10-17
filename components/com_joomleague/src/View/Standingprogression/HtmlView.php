<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Site\View\Standingprogression;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;
final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */ public array $progression = [];
	public function display($tpl = null): void
	{
		$this->progression = $this->getModel()->getProgression();
		PublicViewGuard::requireItem($this->progression, 'project');
		$project = $this->progression['project'];
		$title = Text::sprintf('COM_JOOMLEAGUE_PROGRESSION_PAGE_TITLE', $project->name);
		$input = Factory::getApplication()->getInput();
		(new SeoMetadata())->applyView($this->getDocument(), 'standingprogression', ['project_id' => (int) $project->id, 'stage_id' => $input->getInt('stage_id', 0), 'scope' => $input->getCmd('scope', '')], $title, Text::sprintf('COM_JOOMLEAGUE_SEO_PROJECT_DESCRIPTION', Text::_('COM_JOOMLEAGUE_PROGRESSION_VIEW_TITLE'), $project->name));
		parent::display($tpl);
	}
}
