<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Season;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_SEASON_NEW',
			'edit' => 'COM_JOOMLEAGUE_SEASON_EDIT',
			'icon' => 'calendar',
			'singular' => 'season',
			'details' => 'COM_JOOMLEAGUE_SEASON_DETAILS',
			'main' => ['name', 'alias', 'extended'],
			'side' => [],
			'publishing' => ['published', 'ordering', 'id'],
		];
	}
}
