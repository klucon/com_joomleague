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
use Joomla\CMS\Form\Field\CheckboxesField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Checkbox list of the standings metrics the menu item's "project_id"
 * *request* field's sport profile actually defines — same shape as
 * modules/mod_joomleague_standings/fields/metrics.php, kept as a separate
 * copy since com_menus loads this component's own addfieldpath, not the
 * module's. project_id lives in the "request" group here, hardcoded rather
 * than reusing $this->group (which is "params" for this field itself).
 */
class JFormFieldMetrics extends CheckboxesField
{
	protected $type = 'Metrics';

	protected function getOptions(): array
	{
		$options = parent::getOptions();

		$projectId = (int) $this->form->getValue('project_id', 'request');

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
			$option->checked = false;
			$options[] = $option;
		}

		return $options;
	}
}
