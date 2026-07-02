<?php

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
				['field' => 'jerseynumber', 'label' => 'COM_JOOMLEAGUE_TEAMPLAYER_FIELD_JERSEY', 'class' => 'text-center'],
				['field' => 'position_name', 'label' => 'COM_JOOMLEAGUE_POSITION'],
				['field' => 'team_name', 'label' => 'COM_JOOMLEAGUE_TEAM'],
				['field' => 'active', 'label' => 'COM_JOOMLEAGUE_TEAMPLAYER_FIELD_ACTIVE', 'class' => 'text-center'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'class' => 'text-center'],
			],
		];
	}
}
