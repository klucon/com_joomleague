<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\View\Participant;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Site\Service\ProjectTemplateProvider;
use Joomleague\Component\Joomleague\Site\Service\PublicViewGuard;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $participant = [];
	/** @var array<string,mixed> */
	public array $templateConfig = [];

	public function display($tpl = null): void
	{
		$this->participant = $this->getModel()->getParticipant();
		PublicViewGuard::requireItem($this->participant, 'participant');
		if (isset($this->participant['participant'])) {
			$item = $this->participant['participant'];
			\Joomleague\Component\Joomleague\Site\Service\MediaResolver::participant($item);
			foreach ($this->participant['members'] as $member) $member->picture = \Joomleague\Component\Joomleague\Site\Service\MediaResolver::image('person', $member->picture);
			$provider = new ProjectTemplateProvider(Factory::getContainer()->get(DatabaseInterface::class));
			$projectId = (int) $this->participant['participant']->project_id;
			$this->templateConfig = $provider->supports($projectId, 'participant') ? $provider->resolve($projectId, 'participant') : [];
		}
		$title = isset($this->participant['participant'])
			? (string) $this->participant['participant']->display_name
			: Text::_('COM_JOOMLEAGUE_PARTICIPANT_VIEW_TITLE');
		$item = $this->participant['participant'];
		(new SeoMetadata())->applyView($this->getDocument(), 'participant', [
			'project_id' => (int) $item->project_id,
			'entry_id' => (int) $item->id,
		], $title, Text::sprintf('COM_JOOMLEAGUE_SEO_ENTITY_DESCRIPTION', Text::_('COM_JOOMLEAGUE_PARTICIPANT_VIEW_TITLE'), $title));
		parent::display($tpl);
	}
}
