<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Raceparticipants;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView;

final class HtmlView extends AdminListView
{
	protected function configure(): array
	{
		return [
			'title' => 'COM_JOOMLEAGUE_RACEPARTICIPANTS_TITLE',
			'caption' => 'COM_JOOMLEAGUE_RACEPARTICIPANTS_TITLE',
			'icon' => 'users',
			'singular' => 'raceparticipant',
			'plural' => 'raceparticipants',
			'primary' => 'runner',
			'state' => true,
			'columns' => [
				['field' => 'bib_number', 'label' => 'COM_JOOMLEAGUE_RACE_FIELD_BIB_NUMBER', 'sort' => 'a.bib_number'],
				['field' => 'runner', 'label' => 'COM_JOOMLEAGUE_RACEPARTICIPANT_FIELD_PERSON'],
				['field' => 'project', 'label' => 'COM_JOOMLEAGUE_FIELD_PROJECT'],
				['field' => 'category', 'label' => 'COM_JOOMLEAGUE_RACECATEGORY'],
				['field' => 'sex', 'label' => 'COM_JOOMLEAGUE_RACE_FIELD_SEX'],
				['field' => 'club', 'label' => 'COM_JOOMLEAGUE_MENU_CLUBS'],
				['field' => 'team', 'label' => 'COM_JOOMLEAGUE_MENU_TEAMS'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'sort' => 'a.id'],
			],
		];
	}
}
