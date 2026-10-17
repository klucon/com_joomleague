<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Personnel;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $personnel = [];

	public function display($tpl = null): void
	{
		$this->personnel = $this->getModel()->getPersonnel();
		PublicViewGuard::requireItem($this->personnel, 'project');
		foreach ($this->personnel['groups'] as $people) foreach ($people as $person) $person->picture = \Joomleague\Component\Joomleague\Site\Service\MediaResolver::image('person', $person->picture);
		$title = isset($this->personnel['project'])
			? Text::sprintf('COM_JOOMLEAGUE_PERSONNEL_PAGE_TITLE', $this->personnel['project']->name)
			: Text::_('COM_JOOMLEAGUE_PERSONNEL_VIEW_TITLE');
		$project = $this->personnel['project'];
		(new SeoMetadata())->applyView($this->getDocument(), 'personnel', ['project_id' => (int) $project->id], $title, Text::sprintf('COM_JOOMLEAGUE_SEO_PROJECT_DESCRIPTION', Text::_('COM_JOOMLEAGUE_PERSONNEL_VIEW_TITLE'), $project->name));
		parent::display($tpl);
	}
}
