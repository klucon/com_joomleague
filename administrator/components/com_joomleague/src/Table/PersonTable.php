<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

final class PersonTable extends Table
{
	use AssetTableTrait;
	use MediaFieldTrait;

	protected $_supportNullValue = true;
	public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null) { parent::__construct('#__joomleague_person', 'id', $db, $dispatcher); }
	public function check(): bool
	{
		if (!parent::check()) { return false; }
		$this->firstname = trim((string) $this->firstname); $this->lastname = trim((string) $this->lastname);
		$this->alias = OutputFilter::stringURLSafe(trim((string) $this->alias) ?: trim($this->firstname . ' ' . $this->lastname));
		if ($this->lastname === '') { $this->setError(Text::_('COM_JOOMLEAGUE_PERSON_ERROR_LASTNAME_REQUIRED')); return false; }
		if ($this->email !== '' && filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) { $this->setError(Text::_('COM_JOOMLEAGUE_PERSON_ERROR_EMAIL_INVALID')); return false; }
		if ($this->birthday && $this->deathday && $this->deathday < $this->birthday) { $this->setError(Text::_('COM_JOOMLEAGUE_PERSON_ERROR_DATES_INVALID')); return false; }
		if ((int) $this->height < 0 || (int) $this->weight < 0) { $this->setError(Text::_('COM_JOOMLEAGUE_PERSON_ERROR_DIMENSIONS_INVALID')); return false; }
		$this->normalizeMediaField('picture');
		return true;
	}
}
