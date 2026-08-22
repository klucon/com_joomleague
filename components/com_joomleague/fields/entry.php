<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Lists entries (teams/persons) for the menu item's own "project_id"
 * *request* field — loaded via the classic addfieldpath/JFormField*
 * convention, same reasoning and same class shape as
 * modules/mod_joomleague_standings/fields/entry.php (kept as a separate
 * copy rather than shared, since com_menus' menu item editor loads this
 * component's own addfieldpath, not the module's).
 *
 * project_id lives in the "request" field group here (it becomes part of
 * the menu item's link), not "params" like highlight_entry_id itself, so
 * the group name is hardcoded rather than reusing $this->group.
 */
class JFormFieldEntry extends ListField
{
	protected $type = 'Entry';

	protected function getOptions(): array
	{
		$options = parent::getOptions();

		$projectId = (int) $this->form->getValue('project_id', 'request');

		if ($projectId < 1) {
			return $options;
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('entry.id', 'value'),
				'COALESCE(NULLIF(' . $db->quoteName('entry.display_name') . ", ''), "
					. $db->quoteName('team.name') . ', NULLIF(TRIM(CONCAT('
					. $db->quoteName('person.first_name') . ", ' ', " . $db->quoteName('person.last_name')
					. ")), ''), CONCAT('ID ', " . $db->quoteName('entry.id') . ')) AS ' . $db->quoteName('text'),
			])
			->from($db->quoteName('#__joomleague_project_entry', 'entry'))
			->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
			->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
			->where($db->quoteName('entry.project_id') . ' = :project')
			->bind(':project', $projectId, ParameterType::INTEGER)
			->order($db->quoteName('text') . ' ASC');

		foreach ($db->setQuery($query)->loadObjectList() as $row) {
			$options[] = HTMLHelper::_('select.option', (string) $row->value, (string) $row->text);
		}

		return $options;
	}
}
