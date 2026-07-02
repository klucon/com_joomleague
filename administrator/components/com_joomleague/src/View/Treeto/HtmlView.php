<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Treeto;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_TREETO_NEW',
			'edit' => 'COM_JOOMLEAGUE_TREETO_EDIT',
			'icon' => 'tree-2',
			'singular' => 'treeto',
			'details' => 'COM_JOOMLEAGUE_TREETO_FIELDSET_DETAILS',
			'main' => ['project_id', 'division_id', 'name', 'tree_i', 'global_bestof', 'global_matchday'],
			'side' => ['flags' => 'COM_JOOMLEAGUE_TREETO_FIELDSET_FLAGS'],
			'publishing' => ['published'],
		];
	}
}
