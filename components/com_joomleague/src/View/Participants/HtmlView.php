<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Participants;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $participants = [];

	public function display($tpl = null): void
	{
		$this->participants = $this->getModel()->getParticipants();
		PublicViewGuard::requireItem($this->participants, 'project');
		foreach ($this->participants['participants'] as $participant) \Joomleague\Component\Joomleague\Site\Service\MediaResolver::participant($participant);
		$title = isset($this->participants['project'])
			? Text::sprintf('COM_JOOMLEAGUE_PARTICIPANTS_PAGE_TITLE', $this->participants['project']->name)
			: Text::_('COM_JOOMLEAGUE_PARTICIPANTS_VIEW_TITLE');
		$project = $this->participants['project'];
		(new SeoMetadata())->applyView($this->getDocument(), 'participants', ['project_id' => (int) $project->id], $title, Text::sprintf('COM_JOOMLEAGUE_SEO_PROJECT_DESCRIPTION', Text::_('COM_JOOMLEAGUE_PARTICIPANTS_VIEW_TITLE'), $project->name));
		parent::display($tpl);
	}
}
