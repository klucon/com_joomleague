<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Treetonode;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_TREETONODE_NEW',
			'edit' => 'COM_JOOMLEAGUE_TREETONODE_EDIT',
			'icon' => 'tree-2',
			'singular' => 'treetonode',
			'details' => 'COM_JOOMLEAGUE_TREETONODE_FIELDSET_DETAILS',
			'main' => ['treeto_id', 'node', 'row', 'title', 'content', 'team_id', 'bestof'],
			'side' => ['flags' => 'COM_JOOMLEAGUE_TREETO_FIELDSET_FLAGS'],
			'publishing' => ['published'],
		];
	}
}
