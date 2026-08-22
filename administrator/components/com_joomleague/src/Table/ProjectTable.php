<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

defined('_JEXEC') or die;

use DateTimeZone;
use Joomla\CMS\Access\Rules;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\String\StringHelper;

final class ProjectTable extends Table
{
	protected $_supportNullValue = true;

	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__joomleague_project', 'id', $database);
	}

	public function bind($array, $ignore = '')
	{
		if (isset($array['rules']) && is_array($array['rules'])) {
			$this->setRules(new Rules($array['rules']));
		}

		return parent::bind($array, $ignore);
	}

	public function check(): bool
	{
		foreach (['name', 'alias', 'code', 'external_code', 'project_type', 'timezone', 'default_start_time', 'lifecycle_state'] as $field) {
			$this->{$field} = StringHelper::trim((string) ($this->{$field} ?? ''));
		}

		if ($this->name === '') {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECT_NAME_REQUIRED'));
			return false;
		}

		if ($this->alias === '') {
			$this->alias = OutputFilter::stringURLSafe($this->name);
		}

		if ($this->uuid !== '' && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', (string) $this->uuid) !== 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_UUID_INVALID'));
			return false;
		}

		if ($this->timezone !== '' && !in_array($this->timezone, DateTimeZone::listIdentifiers(), true) && $this->timezone !== 'UTC') {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECT_TIMEZONE_INVALID'));
			return false;
		}

		if ($this->default_start_time !== '' && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $this->default_start_time) !== 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECT_TIME_INVALID'));
			return false;
		}

		if (!in_array($this->current_round_mode, ['manual', 'start', 'end', 'first_match', 'last_match'], true)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECT_ROUND_MODE_INVALID'));
			return false;
		}

		if (!in_array($this->lifecycle_state, ['draft', 'active', 'completed', 'archived'], true)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECT_LIFECYCLE_INVALID'));
			return false;
		}

		if ($this->start_date && $this->end_date && $this->end_date < $this->start_date) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECT_DATE_ORDER'));
			return false;
		}

		if ($this->auto_advance_seconds !== null && $this->auto_advance_seconds !== '' && (int) $this->auto_advance_seconds < 0) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECT_AUTO_ADVANCE_INVALID'));
			return false;
		}

		foreach (['code', 'external_code', 'timezone', 'start_date', 'end_date', 'default_start_time', 'picture'] as $field) {
			if (($this->{$field} ?? '') === '') {
				$this->{$field} = null;
			}
		}
		if (($this->auto_advance_seconds ?? '') === '') {
			$this->auto_advance_seconds = null;
		}

		return parent::check();
	}

	protected function _getAssetName(): string
	{
		return 'com_joomleague.project.' . (int) $this->id;
	}

	protected function _getAssetTitle(): string
	{
		return (string) $this->name;
	}

	protected function _getAssetParentId(?Table $table = null, $id = null): int
	{
		$db = $this->getDatabase();
		$extension = 'com_joomleague';
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__assets'))
			->where($db->quoteName('name') . ' = :extension')
			->bind(':extension', $extension);
		$db->setQuery($query);
		if ($result = (int) $db->loadResult()) {
			return $result;
		}

		return parent::_getAssetParentId($table, $id);
	}
}
