<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Model;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;
use Joomleague\Component\Joomleague\Administrator\Service\PositionCapabilityRepository;
final class PositionModel extends AdminModel implements CurrentUserInterface
{
	use CurrentUserTrait;
	protected $text_prefix = 'COM_JOOMLEAGUE_POSITION';
	public function getTable($name = 'Position', $prefix = 'Administrator', $options = []): Table { return parent::getTable($name, $prefix, $options); }
	public function getForm($data = [], $loadData = true): Form|false { return $this->loadForm('com_joomleague.position', 'position', ['control' => 'jform', 'load_data' => $loadData]); }
	protected function loadFormData(): array|object { $data = Factory::getApplication()->getUserState('com_joomleague.edit.position.data', []); return $data ?: $this->getItem(); }
	protected function prepareTable($table): void
	{
		$now = Factory::getDate()->toSql(); $userId = (int) $this->getCurrentUser()->id;
		if ((int) $table->id === 0) { $table->uuid = UuidFactory::v4(); $table->created = $now; $table->created_by = $userId; $table->ordering = $table->ordering ?: $table->getNextOrder(); }
		else { $table->modified = $now; $table->modified_by = $userId; }
		$table->source = 'local'; $table->source_profile_version_id = null; $table->source_checksum = null; $table->name_key = null;
	}
	protected function canDelete($record): bool
	{
		if (!parent::canDelete($record)) return false;
		$db = $this->getDatabase(); $sportTypeId = (int) $record->sport_type_id; $code = (string) $record->code;
		$query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_project_entry_member', 'member'))
			->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = member.project_entry_id')
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = entry.project_id')
			->where('project.sport_type_id = :sportTypeId')->where('member.role_code = :code')
			->bind(':sportTypeId', $sportTypeId, ParameterType::INTEGER)->bind(':code', $code);
		$count = (int) $db->setQuery($query)->loadResult();
		if ($count > 0) { $this->setError(Text::sprintf('COM_JOOMLEAGUE_ERROR_POSITION_IN_USE', $count)); return false; }
		return true;
	}
	public function getEventCapabilities(int $positionId): array { return (new PositionCapabilityRepository($this->getDatabase()))->events($positionId); }
	public function getStatisticCapabilities(int $positionId): array { return (new PositionCapabilityRepository($this->getDatabase()))->statistics($positionId); }
	public function save($data): bool
	{
		$hasEvents = array_key_exists('assigned_events', $data); $events = array_map('intval', (array) ($data['assigned_events'] ?? []));
		$hasStatistics = array_key_exists('assigned_statistics', $data); $statistics = array_map('intval', (array) ($data['assigned_statistics'] ?? []));
		unset($data['assigned_events'], $data['assigned_statistics']); $db = $this->getDatabase(); $db->transactionStart();
		try { if (!parent::save($data)) { $db->transactionRollback(); return false; } $id=(int)$this->getState($this->getName().'.id');$repo=new PositionCapabilityRepository($db);$actor=(int)$this->getCurrentUser()->id;if($hasEvents)$repo->replaceEvents($id,$events,$actor);if($hasStatistics)$repo->replaceStatistics($id,$statistics,$actor);$db->transactionCommit();return true; }
		catch(\Throwable $e){$db->transactionRollback();$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_POSITION_CAPABILITIES_SAVE'));return false;}
	}
}
