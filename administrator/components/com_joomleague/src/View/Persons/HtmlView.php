<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Persons;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView;

final class HtmlView extends AdminListView
{
	protected function configure(): array
	{
		return [
			'title' => 'COM_JOOMLEAGUE_PERSONS_TITLE',
			'caption' => 'COM_JOOMLEAGUE_PERSONS_TITLE',
			'icon' => 'user',
			'singular' => 'person',
			'plural' => 'persons',
			'primary' => 'lastname',
			'state' => true,
			'toolbar_links' => [
				['url' => 'index.php?option=com_fields&context=com_joomleague.person', 'label' => 'COM_JOOMLEAGUE_CUSTOM_FIELDS', 'icon' => 'icon-list'],
			],
			'columns' => [
				['field' => 'lastname', 'label' => 'COM_JOOMLEAGUE_PERSON_FIELD_LASTNAME', 'sort' => 'a.lastname'],
				['field' => 'firstname', 'label' => 'COM_JOOMLEAGUE_PERSON_FIELD_FIRSTNAME', 'sort' => 'a.firstname'],
				['field' => 'knvbnr', 'label' => 'COM_JOOMLEAGUE_PERSON_FIELD_REGISTRATION', 'sort' => 'a.knvbnr'],
				['field' => 'position', 'label' => 'COM_JOOMLEAGUE_PERSON_FIELD_POSITION', 'type' => 'lang'],
				['field' => 'country', 'label' => 'COM_JOOMLEAGUE_FIELD_COUNTRY', 'type' => 'country'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'sort' => 'a.id'],
			],
		];
	}
}
