<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Site\View\Statisticsoverview;
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;
final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */ public array $overview = [];
	public function display($tpl = null): void
	{
		$this->overview = $this->getModel()->getOverview();
		PublicViewGuard::requireItem($this->overview, 'project');
		$project = $this->overview['project'];
		$title = Text::sprintf('COM_JOOMLEAGUE_STATSOVERVIEW_PAGE_TITLE', $project->name);
		(new SeoMetadata())->applyView($this->getDocument(), 'statisticsoverview', ['project_id' => (int) $project->id], $title, Text::sprintf('COM_JOOMLEAGUE_SEO_PROJECT_DESCRIPTION', Text::_('COM_JOOMLEAGUE_STATSOVERVIEW_VIEW_TITLE'), $project->name));
		parent::display($tpl);
	}
}
