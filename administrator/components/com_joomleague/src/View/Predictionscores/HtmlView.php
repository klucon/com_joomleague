<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Predictionscores;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView;

final class HtmlView extends AdminListView
{
	protected function configure(): array
	{
		return [
			'title' => 'COM_JOOMLEAGUE_PREDICTIONSCORES_TITLE',
			'caption' => 'COM_JOOMLEAGUE_PREDICTIONSCORES_TITLE',
			'icon' => 'chart',
			'singular' => 'predictionscore',
			'plural' => 'predictionscores',
			'primary' => 'user_name',
			'state' => false,
			'can_create' => false,
			'can_edit' => false,
			'can_delete' => false,
			'toolbar_links' => [
				['url' => 'index.php?option=com_joomleague&view=predictiongames', 'label' => 'COM_JOOMLEAGUE_PREDICTIONGAMES_TITLE', 'icon' => 'arrow-left'],
				['url' => 'index.php?option=com_joomleague&view=predictiontips', 'label' => 'COM_JOOMLEAGUE_PREDICTIONTIPS_TITLE', 'icon' => 'list'],
			],
			'columns' => [
				['field' => 'user_name', 'label' => 'COM_JOOMLEAGUE_PREDICTIONTIP_USER', 'sort' => 'u.name'],
				['field' => 'game_name', 'label' => 'COM_JOOMLEAGUE_PREDICTIONGAMES_TITLE', 'sort' => 'g.name'],
				['field' => 'round_name', 'label' => 'COM_JOOMLEAGUE_ROUNDS_TITLE', 'sort' => 'r.name'],
				['field' => 'tips', 'label' => 'COM_JOOMLEAGUE_PREDICTIONGAME_TIP_COUNT', 'sort' => 'a.tips'],
				['field' => 'exact_hits', 'label' => 'COM_JOOMLEAGUE_PREDICTIONSCORE_EXACT_HITS', 'sort' => 'a.exact_hits'],
				['field' => 'tendency_hits', 'label' => 'COM_JOOMLEAGUE_PREDICTIONSCORE_TENDENCY_HITS', 'sort' => 'a.tendency_hits'],
				['field' => 'points', 'label' => 'COM_JOOMLEAGUE_PREDICTIONTIP_POINTS', 'sort' => 'a.points'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'sort' => 'a.id'],
			],
		];
	}
}
