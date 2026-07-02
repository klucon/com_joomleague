<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Club;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_CLUB_NEW',
			'edit' => 'COM_JOOMLEAGUE_CLUB_EDIT',
			'icon' => 'home',
			'singular' => 'club',
			'details' => 'COM_JOOMLEAGUE_CLUB_DETAILS',
			'main' => ['name', 'alias', 'create_team', 'create_stadium', 'standard_playground', 'founded', 'dissolved'],
			'side' => [
				'address' => 'COM_JOOMLEAGUE_CLUB_FIELDSET_ADDRESS',
				'contact' => 'COM_JOOMLEAGUE_CLUB_FIELDSET_CONTACT',
				'management' => 'COM_JOOMLEAGUE_CLUB_FIELDSET_MANAGEMENT',
				'logos' => 'COM_JOOMLEAGUE_CLUB_LOGOS',
				'description' => 'COM_JOOMLEAGUE_CLUB_FIELDSET_DESCRIPTION',
			],
			'publishing' => ['ordering', 'id'],
		];
	}
}
