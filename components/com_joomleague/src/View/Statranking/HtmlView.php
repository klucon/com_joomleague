<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Statranking;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */ public array $ranking = [];
	public function display($tpl = null): void
	{
		$this->ranking = $this->getModel()->getRanking();
		PublicViewGuard::requireItem($this->ranking, 'project');
		$title = isset($this->ranking['project']) ? Text::sprintf('COM_JOOMLEAGUE_STATRANKING_PAGE_TITLE', $this->ranking['project']->name) : Text::_('COM_JOOMLEAGUE_STATRANKING_VIEW_TITLE');
		$project = $this->ranking['project'];
		(new SeoMetadata())->applyView($this->getDocument(), 'statranking', ['project_id' => (int) $project->id, 'statistic_code' => Factory::getApplication()->getInput()->getCmd('statistic_code', '')], $title, Text::sprintf('COM_JOOMLEAGUE_SEO_PROJECT_DESCRIPTION', Text::_('COM_JOOMLEAGUE_STATRANKING_VIEW_TITLE'), $project->name));
		parent::display($tpl);
	}
}
