<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Predictiontips;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView;

final class HtmlView extends AdminListView
{
	protected function configure(): array
	{
		return [
			'title' => 'COM_JOOMLEAGUE_PREDICTIONTIPS_TITLE',
			'caption' => 'COM_JOOMLEAGUE_PREDICTIONTIPS_TITLE',
			'icon' => 'list',
			'singular' => 'predictiontip',
			'plural' => 'predictiontips',
			'primary' => 'user_name',
			'state' => false,
			'can_create' => false,
			'can_edit' => false,
			'can_delete' => false,
			'toolbar_links' => [
				['url' => 'index.php?option=com_joomleague&view=predictiongames', 'label' => 'COM_JOOMLEAGUE_PREDICTIONGAMES_TITLE', 'icon' => 'arrow-left'],
			],
			'columns' => [
				['field' => 'user_name', 'label' => 'COM_JOOMLEAGUE_PREDICTIONTIP_USER', 'sort' => 'u.name'],
				['field' => 'game_name', 'label' => 'COM_JOOMLEAGUE_PREDICTIONGAMES_TITLE', 'sort' => 'g.name'],
				['field' => 'match_date', 'label' => 'COM_JOOMLEAGUE_MATCH_FIELD_DATE', 'sort' => 'm.match_date'],
				['field' => 'match_name', 'label' => 'COM_JOOMLEAGUE_EDIT_MATCHES'],
				['field' => 'tip_score', 'label' => 'COM_JOOMLEAGUE_PREDICTIONTIP_TIP'],
				['field' => 'result_score', 'label' => 'COM_JOOMLEAGUE_MATCH_RESULTS'],
				['field' => 'points', 'label' => 'COM_JOOMLEAGUE_PREDICTIONTIP_POINTS', 'sort' => 'a.points'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'sort' => 'a.id'],
			],
		];
	}
}
