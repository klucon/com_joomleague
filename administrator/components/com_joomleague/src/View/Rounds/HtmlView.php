<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Rounds;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView;

final class HtmlView extends AdminListView
{
	public ?object $projectContext = null;

	public function display($tpl = null): void
	{
		$this->projectContext = $this->getModel()->getProjectContext();
		parent::display($tpl);
	}

	protected function configure(): array
	{
		$id = (int) $this->getModel()->getState('filter.project_id');

		return [
			'title' => 'COM_JOOMLEAGUE_ROUNDS_TITLE',
			'caption' => 'COM_JOOMLEAGUE_ROUNDS_TITLE',
			'icon' => 'calendar',
			'singular' => 'round',
			'plural' => 'rounds',
			'primary' => 'name',
			'state' => true,
			'toolbar_links' => [
				['url' => 'index.php?option=com_joomleague&view=projectpanel&project_id=' . $id, 'label' => 'COM_JOOMLEAGUE_BACK_TO_PROJECT_PANEL', 'icon' => 'arrow-left'],
				['url' => 'index.php?option=com_joomleague&view=schedule&project_id=' . $id, 'label' => 'COM_JOOMLEAGUE_GENERATE_SCHEDULE', 'icon' => 'refresh'],
			],
			'columns' => [
				['field' => 'roundcode', 'label' => 'COM_JOOMLEAGUE_ROUND_FIELD_CODE', 'sort' => 'a.roundcode'],
				['field' => 'name', 'label' => 'COM_JOOMLEAGUE_FIELD_NAME', 'sort' => 'a.name'],
				['field' => 'round_date_first', 'label' => 'COM_JOOMLEAGUE_ROUND_FIELD_FIRST_DATE', 'sort' => 'a.round_date_first'],
				['field' => 'round_date_last', 'label' => 'COM_JOOMLEAGUE_ROUND_FIELD_LAST_DATE'],
				['field' => 'round_date_first', 'label' => 'COM_JOOMLEAGUE_ROUND_MOVE_LABEL', 'type' => 'roundmove'],
				['field' => 'match_count', 'label' => 'COM_JOOMLEAGUE_EDIT_MATCHES', 'type' => 'roundmatches'],
				['field' => 'result_count', 'label' => 'COM_JOOMLEAGUE_MATCH_RESULTS', 'type' => 'roundmatches'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'sort' => 'a.id'],
			],
		];
	}
}
