<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Table;
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;
final class PositionTable extends Table
{
	protected $_supportNullValue = true;
	protected $_trackAssets = false;
	public function __construct(DatabaseDriver $database) { parent::__construct('#__joomleague_sport_position', 'id', $database); $this->_trackAssets = false; }
	public function check(): bool
	{
		$this->name = StringHelper::trim((string) $this->name);
		$this->code = strtolower(StringHelper::trim((string) $this->code));
		$this->person_type = StringHelper::trim((string) $this->person_type);
		$this->lineup_group = StringHelper::trim((string) ($this->lineup_group ?? '')) ?: null;
		$this->parent_id = (int) ($this->parent_id ?? 0) ?: null;
		if ((int) $this->sport_type_id < 1 || $this->name === '' || $this->person_type === '') { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_POSITION_REQUIRED')); return false; }
		if (preg_match('/^[a-z][a-z0-9_]{0,99}$/', $this->code) !== 1) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_POSITION_CODE_INVALID')); return false; }
		if (preg_match('/^[a-z][a-z0-9_]{0,99}$/', $this->person_type) !== 1 || ($this->lineup_group !== null && preg_match('/^[a-z][a-z0-9_]{0,99}$/', $this->lineup_group) !== 1)) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_POSITION_CLASSIFICATION_INVALID')); return false; }
		$db = $this->getDatabase();
		$id = (int) $this->id;
		$sportTypeId = (int) $this->sport_type_id;
		$query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_sport_position'))->where('sport_type_id = :sportTypeId')->where('code = :code')->where('id <> :id')->bind(':sportTypeId', $sportTypeId, ParameterType::INTEGER)->bind(':code', $this->code)->bind(':id', $id, ParameterType::INTEGER);
		if ((int) $db->setQuery($query)->loadResult() > 0) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_POSITION_CODE_DUPLICATE')); return false; }
		if ($this->parent_id !== null) {
			if ((int) $this->parent_id === $id) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_POSITION_PARENT_INVALID')); return false; }
			$parentId = (int) $this->parent_id;
			$query = $db->getQuery(true)->select('sport_type_id')->from($db->quoteName('#__joomleague_sport_position'))->where('id = :parentId')->bind(':parentId', $parentId, ParameterType::INTEGER);
			if ((int) $db->setQuery($query)->loadResult() !== $sportTypeId) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_POSITION_PARENT_INVALID')); return false; }
			$ancestorId = $parentId;
			for ($depth = 0; $depth < 100 && $ancestorId > 0; $depth++) {
				if ($ancestorId === $id) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_POSITION_PARENT_CYCLE')); return false; }
				$query = $db->getQuery(true)->select('parent_id')->from($db->quoteName('#__joomleague_sport_position'))->where('id = :ancestorId')->bind(':ancestorId', $ancestorId, ParameterType::INTEGER);
				$ancestorId = (int) $db->setQuery($query)->loadResult();
			}
		}
		return parent::check();
	}
}
