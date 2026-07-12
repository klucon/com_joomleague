<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Racecategories;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView;

final class HtmlView extends AdminListView
{
	protected function configure(): array
	{
		return [
			'title' => 'COM_JOOMLEAGUE_RACECATEGORIES_TITLE',
			'caption' => 'COM_JOOMLEAGUE_RACECATEGORIES_TITLE',
			'icon' => 'tags',
			'singular' => 'racecategory',
			'plural' => 'racecategories',
			'primary' => 'name',
			'state' => true,
			'columns' => [
				['field' => 'name', 'label' => 'COM_JOOMLEAGUE_FIELD_NAME', 'sort' => 'a.name'],
				['field' => 'project', 'label' => 'COM_JOOMLEAGUE_FIELD_PROJECT'],
				['field' => 'sex', 'label' => 'COM_JOOMLEAGUE_RACE_FIELD_SEX'],
				['field' => 'age_min', 'label' => 'COM_JOOMLEAGUE_RACECATEGORY_FIELD_AGE_MIN'],
				['field' => 'age_max', 'label' => 'COM_JOOMLEAGUE_RACECATEGORY_FIELD_AGE_MAX'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'sort' => 'a.id'],
			],
		];
	}
}
