<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  mod_joomleague_standings
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\CheckboxesField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Checkbox list of the standings metrics the module's own, currently saved
 * "project_id" param's sport profile actually defines — loaded via the
 * classic addfieldpath/JFormField* convention for the same reason as
 * EntryField: the admin module editor builds this form without booting the
 * module, so the module's own namespace is never autoloaded there.
 *
 * This doubles as the answer to "how do I know what's available" — the
 * checkbox list itself is the catalogue, labelled with the same translated
 * text the rendered table's column headers use.
 */
class JFormFieldMetrics extends CheckboxesField
{
	protected $type = 'Metrics';

	protected function getOptions(): array
	{
		$options = parent::getOptions();

		$projectId = (int) $this->form->getValue('project_id', $this->group);

		if ($projectId < 1) {
			return $options;
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select($db->quoteName('version.payload_json'))
			->from($db->quoteName('#__joomleague_project', 'project'))
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->where($db->quoteName('project.id') . ' = :project')
			->bind(':project', $projectId, ParameterType::INTEGER);

		$payload = $db->setQuery($query)->loadResult();

		if ($payload === null) {
			return $options;
		}

		$profile = json_decode($payload, true);
		$metrics = $profile['standings']['calculation']['metrics'] ?? null;

		if (!\is_array($metrics)) {
			return $options;
		}

		// Same site-side language file the module's own template uses for
		// column headers (see StandingsHelper), so the picker's labels
		// always match what actually ends up printed on the page.
		$language = Factory::getLanguage();
		$language->load('com_joomleague', JPATH_SITE)
			|| $language->load('com_joomleague', JPATH_SITE . '/components/com_joomleague');

		foreach ($metrics as $metric) {
			$code = \is_array($metric) ? ($metric['code'] ?? null) : null;

			if (!\is_string($code) || $code === '') {
				continue;
			}

			$label = Text::_('COM_JOOMLEAGUE_STANDING_METRIC_' . strtoupper($code));
			$option = HTMLHelper::_('select.option', $code, $label . ' (' . $code . ')');
			// The checkboxes layout (layouts/joomla/form/field/checkboxes.php)
			// reads ->checked directly for the "no stored value yet" default
			// state; HTMLHelper::_('select.option', ...) does not set it.
			$option->checked = false;
			$options[] = $option;
		}

		return $options;
	}
}
