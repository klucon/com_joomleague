<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Teamstaffs;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView;

final class HtmlView extends AdminListView
{
	protected function configure(): array
	{
		return [
			'title' => 'COM_JOOMLEAGUE_TEAMSTAFFS_TITLE',
			'caption' => 'COM_JOOMLEAGUE_TEAMSTAFFS_TITLE',
			'icon' => 'users',
			'singular' => 'teamstaff',
			'plural' => 'teamstaffs',
			'primary' => 'staff_name',
			'state' => true,
			'columns' => [
				['field' => 'staff_name', 'label' => 'COM_JOOMLEAGUE_PERSON', 'sort' => 'pe.lastname'],
				['field' => 'position_name', 'label' => 'COM_JOOMLEAGUE_POSITION'],
				['field' => 'team_name', 'label' => 'COM_JOOMLEAGUE_TEAM'],
				['field' => 'active', 'label' => 'COM_JOOMLEAGUE_TEAMSTAFF_FIELD_ACTIVE', 'class' => 'text-center'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'class' => 'text-center'],
			],
		];
	}
}
