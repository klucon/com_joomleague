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

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $participant = [];
	/** @var array<string,mixed> */
	public array $templateConfig = [];

	public function display($tpl = null): void
	{
		$this->participant = $this->getModel()->getParticipant();
		if (isset($this->participant['participant'])) {
			$provider = new ProjectTemplateProvider(Factory::getContainer()->get(DatabaseInterface::class));
			$projectId = (int) $this->participant['participant']->project_id;
			$this->templateConfig = $provider->supports($projectId, 'participant') ? $provider->resolve($projectId, 'participant') : [];
		}
		$title = isset($this->participant['participant'])
			? (string) $this->participant['participant']->display_name
			: Text::_('COM_JOOMLEAGUE_PARTICIPANT_VIEW_TITLE');
		$this->getDocument()->setTitle($title);
		parent::display($tpl);
	}
}
