<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Matchlineup;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class HtmlView extends BaseHtmlView
{
	public object $match;
	public array $profile = [];
	public array $participants = [];
	public ?object $participant = null;
	public array $availableMembers = [];
	public array $assignedMembers = [];
	public array $substitutions = [];
	public array $substitutionPhases = [];
	public bool $substitutionsSupported = false;

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		$input = $app->getInput();
		$matchId = $input->getInt('match_id');
		if ($matchId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_MATCH_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}
		$participantId = $input->getInt('participant_id');
		try {
			$context = $this->getModel()->getContext($matchId);
			$this->match = $context['match']; $this->profile = $context['profile']; $this->participants = $context['participants'];
			foreach ($this->participants as $participant) if ((int) $participant->id === $participantId) $this->participant = $participant;
			if (!$this->participant) {
				foreach ($this->participants as $participant) {
					if ((int) $participant->available_member_count > 0) {
						$this->participant = $participant;
						break;
					}
				}

				$this->participant ??= $this->participants[0] ?? null;
				$participantId = (int) ($this->participant->id ?? 0);
			}
			if ($this->participant) {
				$this->availableMembers = $this->getModel()->getAvailableMembers($matchId, $participantId);
				$this->assignedMembers = $this->getModel()->getAssignedMembers($matchId, $participantId);
				$this->substitutions = $this->getModel()->getSubstitutions($matchId, $participantId);
				$this->substitutionsSupported = ($this->profile['lineup']['substitutions']['supported'] ?? false) === true || (int) ($this->profile['lineup']['default_substitutes_allowed'] ?? 0) > 0;
				foreach ($this->profile['match']['score']['segment_types'] ?? [] as $segment) $this->substitutionPhases[(string) $segment['code']] = (string) $segment['name_key'];
			}
		} catch (\Throwable $exception) {
			throw new GenericDataException($exception->getMessage(), 500);
		}

		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectId($matchId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_MATCHLINEUP_TITLE'), 'users');
		if ($this->participant && Factory::getApplication()->getIdentity()->authorise('joomleague.project.edit.lineup', $asset)) {
			ToolbarHelper::custom('matchlineup.assign', 'plus', 'plus', 'COM_JOOMLEAGUE_MATCHLINEUP_ASSIGN', false);
			ToolbarHelper::custom('matchlineup.remove', 'minus', 'minus', 'COM_JOOMLEAGUE_MATCHLINEUP_REMOVE', false);
		}
		ToolbarHelper::link('index.php?option=com_joomleague&view=matches&round_id=' . (int) ($this->match->round_id ?? 0), 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
