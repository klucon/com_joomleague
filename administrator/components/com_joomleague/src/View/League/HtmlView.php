<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\League;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_LEAGUE_NEW',
			'edit' => 'COM_JOOMLEAGUE_LEAGUE_EDIT',
			'icon' => 'list',
			'singular' => 'league',
			'details' => 'COM_JOOMLEAGUE_LEAGUE_DETAILS',
			'main' => ['name', 'middle_name', 'short_name', 'alias', 'country', 'extended'],
			'side' => [],
			'publishing' => ['ordering', 'id'],
		];
	}
}
