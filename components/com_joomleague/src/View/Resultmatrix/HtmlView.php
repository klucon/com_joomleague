<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Resultmatrix;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $matrix = [];

	public function display($tpl = null): void
	{
		$this->matrix = $this->getModel()->getMatrix();
		PublicViewGuard::requireItem($this->matrix, 'project');
		$title = isset($this->matrix['project'])
			? Text::sprintf('COM_JOOMLEAGUE_RESULTMATRIX_PAGE_TITLE', $this->matrix['project']->name)
			: Text::_('COM_JOOMLEAGUE_RESULTMATRIX_VIEW_TITLE');
		$project = $this->matrix['project'];
		(new SeoMetadata())->applyView($this->getDocument(), 'resultmatrix', ['project_id' => (int) $project->id, 'stage_id' => Factory::getApplication()->getInput()->getInt('stage_id', 0)], $title, Text::sprintf('COM_JOOMLEAGUE_SEO_PROJECT_DESCRIPTION', Text::_('COM_JOOMLEAGUE_RESULTMATRIX_VIEW_TITLE'), $project->name));
		parent::display($tpl);
	}
}
