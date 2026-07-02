<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Teamstaff;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_TEAMSTAFF_NEW',
			'edit' => 'COM_JOOMLEAGUE_TEAMSTAFF_EDIT',
			'icon' => 'users',
			'singular' => 'teamstaff',
			'details' => 'COM_JOOMLEAGUE_FIELDSET_DETAILS',
			'main' => ['projectteam_id', 'person_id', 'project_position_id', 'active', 'notes', 'alias'],
			'side' => [
				'status' => 'COM_JOOMLEAGUE_TEAMSTAFF_FIELDSET_STATUS',
				'media' => 'COM_JOOMLEAGUE_FIELDSET_MEDIA',
			],
			'publishing' => ['published', 'ordering'],
		];
	}
}
