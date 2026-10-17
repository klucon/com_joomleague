<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Site\View\Comparison;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;
final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */ public array $comparison = [];
	public function display($tpl = null): void
	{
		$this->comparison = $this->getModel()->getComparison();
		PublicViewGuard::requireItem($this->comparison, 'project');
		$project = $this->comparison['project'];
		$title = Text::sprintf('COM_JOOMLEAGUE_COMPARISON_PAGE_TITLE', $project->name);
		$input = Factory::getApplication()->getInput();
		(new SeoMetadata())->applyView($this->getDocument(), 'comparison', ['project_id' => (int) $project->id, 'first_id' => $input->getInt('first_id', 0), 'second_id' => $input->getInt('second_id', 0)], $title, Text::sprintf('COM_JOOMLEAGUE_SEO_PROJECT_DESCRIPTION', Text::_('COM_JOOMLEAGUE_COMPARISON_VIEW_TITLE'), $project->name));
		parent::display($tpl);
	}
}
