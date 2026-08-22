<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\String\StringHelper;

final class VenueTable extends Table
{
	protected $_supportNullValue = true;
	protected $_trackAssets = false;

	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__joomleague_venue', 'id', $database);
		$this->_trackAssets = false;
	}

	public function check(): bool
	{
		$this->owner_club_id = (int) ($this->owner_club_id ?? 0) ?: null;
		foreach (['name', 'alias', 'short_name', 'nickname', 'address', 'postal_code', 'city', 'region'] as $field) {
			$this->{$field} = StringHelper::trim((string) ($this->{$field} ?? ''));
		}
		$this->country_code = strtoupper(StringHelper::trim((string) ($this->country_code ?? ''))) ?: null;
		foreach (['timezone', 'website', 'picture', 'external_code'] as $field) {
			$this->{$field} = StringHelper::trim((string) ($this->{$field} ?? '')) ?: null;
		}
		$this->latitude = $this->normaliseDecimal($this->latitude ?? null);
		$this->longitude = $this->normaliseDecimal($this->longitude ?? null);
		$this->capacity = ($this->capacity ?? '') === '' ? null : (int) $this->capacity;

		if ($this->name === '') {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_VENUE_NAME_REQUIRED'));
			return false;
		}
		if (($this->latitude === null) !== ($this->longitude === null)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_VENUE_COORDINATES_PAIR'));
			return false;
		}
		if (($this->latitude !== null && ($this->latitude < -90 || $this->latitude > 90)) || ($this->longitude !== null && ($this->longitude < -180 || $this->longitude > 180))) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_VENUE_COORDINATES_RANGE'));
			return false;
		}
		if ($this->capacity !== null && $this->capacity < 0) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_VENUE_CAPACITY'));
			return false;
		}
		if ($this->alias === '') {
			$this->alias = OutputFilter::stringURLSafe($this->name);
		}
		if ($this->uuid !== '' && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', (string) $this->uuid) !== 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_UUID_INVALID'));
			return false;
		}

		return parent::check();
	}

	private function normaliseDecimal(mixed $value): ?float
	{
		return $value === null || $value === '' ? null : (float) $value;
	}
}
