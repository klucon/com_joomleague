<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Raceresults;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView;
use Joomla\CMS\Session\Session;

final class HtmlView extends AdminListView
{
	protected function configure(): array
	{
		return [
			'title' => 'COM_JOOMLEAGUE_RACERESULTS_TITLE',
			'caption' => 'COM_JOOMLEAGUE_RACERESULTS_TITLE',
			'icon' => 'chart',
			'singular' => 'raceresult',
			'plural' => 'raceresults',
			'primary' => 'runner',
			'state' => true,
			'toolbar_links' => [
				[
					'url' => 'index.php?option=com_joomleague&task=raceresults.recalculate&' . Session::getFormToken() . '=1',
					'label' => 'COM_JOOMLEAGUE_RACERESULTS_RECALCULATE',
					'icon' => 'refresh',
				],
			],
			'columns' => [
				['field' => 'overall_place', 'label' => 'COM_JOOMLEAGUE_RACERESULT_FIELD_OVERALL_PLACE', 'sort' => 'a.overall_place'],
				['field' => 'bib_number', 'label' => 'COM_JOOMLEAGUE_RACE_FIELD_BIB_NUMBER', 'sort' => 'a.bib_number'],
				['field' => 'runner', 'label' => 'COM_JOOMLEAGUE_RACEPARTICIPANT_FIELD_PERSON'],
				['field' => 'duration_text', 'label' => 'COM_JOOMLEAGUE_RACERESULT_FIELD_DURATION_TEXT', 'sort' => 'a.duration_ms'],
				['field' => 'status', 'label' => 'COM_JOOMLEAGUE_RACE_FIELD_STATUS'],
				['field' => 'category', 'label' => 'COM_JOOMLEAGUE_RACECATEGORY'],
				['field' => 'category_place', 'label' => 'COM_JOOMLEAGUE_RACERESULT_FIELD_CATEGORY_PLACE'],
				['field' => 'sex_place', 'label' => 'COM_JOOMLEAGUE_RACERESULT_FIELD_SEX_PLACE'],
				['field' => 'project', 'label' => 'COM_JOOMLEAGUE_FIELD_PROJECT'],
				['field' => 'round_name', 'label' => 'COM_JOOMLEAGUE_PROJECT_ROUNDS'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'sort' => 'a.id'],
			],
		];
	}
}
