<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Matchdata;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as Base;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends Base
{
	private const SECTIONS = [
		'players' => 'COM_JOOMLEAGUE_MATCH_PLAYERS',
		'events' => 'COM_JOOMLEAGUE_MY_EVENTS',
		'statistics' => 'COM_JOOMLEAGUE_MY_STATISTICS',
		'referees' => 'COM_JOOMLEAGUE_PROJECT_REFEREES',
		'staff' => 'COM_JOOMLEAGUE_TEAMSTAFFS_TITLE',
	];

	public object $match;
	public string $section;
	public array $rows;
	public array $types = [];
	public array $players = [];
	public array $referees = [];
	public array $positions = [];
	public array $staff = [];
	public array $staffPositions = [];

	public function display($tpl = null): void
	{
		$model = $this->getModel();
		$matchId = (int) $model->getState('match_id');
		$this->section = (string) $model->getState('section', 'events');
		$this->match = $model->getContext($matchId);
		$this->players = $model->getPlayers($matchId);

		if ($this->section === 'events') {
			$this->rows = $model->getEvents($matchId);
			$this->types = $model->getEventTypes((int) $this->match->project_id);
		} elseif ($this->section === 'players') {
			$this->rows = $model->getMatchPlayers($matchId);
			$this->positions = $model->getPlayerPositions((int) $this->match->project_id);
		} elseif ($this->section === 'statistics') {
			$this->rows = $model->getStatistics($matchId);
			$this->types = $model->getStatisticsTypes((int) $this->match->project_id);
		} elseif ($this->section === 'staff') {
			$this->rows = $model->getMatchStaff($matchId);
			$this->staff = $model->getStaff($matchId);
			$this->staffPositions = $model->getStaffPositions((int) $this->match->project_id);
		} else {
			$this->section = 'referees';
			$this->rows = $model->getReferees($matchId);
			$this->referees = $model->getProjectReferees((int) $this->match->project_id);
			$this->positions = $model->getRefereePositions((int) $this->match->project_id);
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	private function addToolbar(): void
	{
		ToolbarHelper::title(
			$this->match->home . ' - ' . $this->match->away . ': ' . Text::_(self::SECTIONS[$this->section]),
			'list'
		);
		ToolbarHelper::apply('matchdata.save', 'JTOOLBAR_APPLY');
		ToolbarHelper::save('matchdata.save2close', 'COM_JOOMLEAGUE_SAVE_AND_CLOSE');
		ToolbarHelper::link(
			'index.php?option=com_joomleague&view=matches&round_id=' . (int) $this->match->round_id,
			Text::_('COM_JOOMLEAGUE_BACK_TO_MATCHES'),
			'arrow-left'
		);
		ToolbarHelper::link(
			'index.php?option=com_joomleague&view=projectpanel&project_id=' . (int) $this->match->project_id,
			Text::_('COM_JOOMLEAGUE_BACK_TO_PROJECT_PANEL'),
			'home'
		);

		foreach (self::SECTIONS as $section => $label) {
			if ($section === $this->section) {
				continue;
			}

			ToolbarHelper::link(
				'index.php?option=com_joomleague&view=matchdata&match_id=' . (int) $this->match->id . '&section=' . $section,
				Text::_($label),
				'list'
			);
		}
	}
}
