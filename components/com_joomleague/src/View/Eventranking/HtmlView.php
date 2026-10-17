<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Site\View\Eventranking;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;
final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */ public array $ranking = [];
	public function display($tpl = null): void
	{
		$this->ranking = $this->getModel()->getRanking();
		PublicViewGuard::requireItem($this->ranking, 'project');
		$project = $this->ranking['project'];
		$title = Text::sprintf('COM_JOOMLEAGUE_EVENTRANKING_PAGE_TITLE', $project->name);
		(new SeoMetadata())->applyView($this->getDocument(), 'eventranking', ['project_id' => (int) $project->id, 'event_code' => Factory::getApplication()->getInput()->getCmd('event_code', '')], $title, Text::sprintf('COM_JOOMLEAGUE_SEO_PROJECT_DESCRIPTION', Text::_('COM_JOOMLEAGUE_EVENTRANKING_VIEW_TITLE'), $project->name));
		parent::display($tpl);
	}
}
