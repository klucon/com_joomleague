<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Teamplayers;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView;

final class HtmlView extends AdminListView
{
	protected function configure(): array
	{
		return [
			'title' => 'COM_JOOMLEAGUE_TEAMPLAYERS_TITLE',
			'caption' => 'COM_JOOMLEAGUE_TEAMPLAYERS_TITLE',
			'icon' => 'users',
			'singular' => 'teamplayer',
			'plural' => 'teamplayers',
			'primary' => 'player_name',
			'state' => true,
			'columns' => [
				['field' => 'player_name', 'label' => 'COM_JOOMLEAGUE_PERSON', 'sort' => 'pe.lastname'],
				['field' => 'picture', 'label' => 'COM_JOOMLEAGUE_FIELD_IMAGE', 'type' => 'image', 'image_placeholder' => 'team_player_picture'],
				['field' => 'jerseynumber', 'label' => 'COM_JOOMLEAGUE_TEAMPLAYER_FIELD_JERSEY', 'class' => 'text-center'],
				['field' => 'position_name', 'label' => 'COM_JOOMLEAGUE_POSITION', 'type' => 'lang'],
				['field' => 'team_name', 'label' => 'COM_JOOMLEAGUE_TEAM'],
				['field' => 'active', 'label' => 'COM_JOOMLEAGUE_TEAMPLAYER_FIELD_ACTIVE', 'class' => 'text-center'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'class' => 'text-center'],
			],
		];
	}
}
