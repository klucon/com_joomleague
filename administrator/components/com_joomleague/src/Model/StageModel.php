<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class StageModel extends AdminModel implements CurrentUserInterface
{
	use CurrentUserTrait;

	protected $text_prefix = 'COM_JOOMLEAGUE_STAGE';

	public function getTable($name = 'Stage', $prefix = 'Administrator', $options = []): Table
	{
		return parent::getTable($name, $prefix, $options);
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		return $this->loadForm('com_joomleague.stage', 'stage', ['control' => 'jform', 'load_data' => $loadData]);
	}

	protected function loadFormData(): array|object
	{
		$data = Factory::getApplication()->getUserState('com_joomleague.edit.stage.data', []);

		if ($data) {
			return $data;
		}

		$item = $this->getItem();
		if ((int) ($item->project_id ?? 0) < 1) $item->project_id = Factory::getApplication()->getInput()->getInt('project_id');

		return $item;
	}

	public function getProject(): object
	{
		$item = $this->getItem();
		$projectId = (int) ($item->project_id ?? Factory::getApplication()->getInput()->getInt('project_id'));

		return (new ProjectContextRepository($this->getDatabase()))->get($projectId);
	}

	public function save($data): bool
	{
		$id = (int) ($data['id'] ?? 0);

		try {
			$projectId = $id > 0 ? $this->storedProjectId($id) : (int) ($data['project_id'] ?? 0);
			(new ProjectContextRepository($this->getDatabase()))->get($projectId);
		} catch (\Throwable) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_PROJECT_INVALID'));

			return false;
		}

		$data['project_id'] = $projectId;
		if ($id === 0 && trim((string) ($data['code'] ?? '')) === '') {
			$data['code'] = $this->availableCode($projectId, (string) ($data['name'] ?? 'stage'));
		}

		return parent::save($data);
	}

	protected function prepareTable($table): void
	{
		$now = Factory::getDate()->toSql();
		$userId = (int) $this->getCurrentUser()->id;

		if ((int) $table->id === 0) {
			$table->uuid = UuidFactory::v4();
			$table->created = $now;
			$table->created_by = $userId;
			$table->ordering = $table->ordering ?: $table->getNextOrder('project_id = ' . (int) $table->project_id);
		} else {
			$table->modified = $now;
			$table->modified_by = $userId;
		}
	}

	private function storedProjectId(int $id): int
	{
		$query = $this->getDatabase()->getQuery(true)->select($this->getDatabase()->quoteName('project_id'))
			->from($this->getDatabase()->quoteName('#__joomleague_project_stage'))
			->where($this->getDatabase()->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
		$projectId = (int) $this->getDatabase()->setQuery($query)->loadResult();

		if ($projectId < 1) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_PROJECT_INVALID'));

		return $projectId;
	}

	private function availableCode(int $projectId, string $name): string
	{
		$base = str_replace('-', '_', ApplicationHelper::stringURLSafe($name)) ?: 'stage';
		$code = $base;
		$suffix = 2;

		while (true) {
			$query = $this->getDatabase()->getQuery(true)->select('COUNT(*)')
				->from($this->getDatabase()->quoteName('#__joomleague_project_stage'))
				->where('project_id = :project')->where('code = :code')
				->bind(':project', $projectId, ParameterType::INTEGER)->bind(':code', $code);
			if ((int) $this->getDatabase()->setQuery($query)->loadResult() === 0) return $code;
			$code = $base . '-' . $suffix++;
		}
	}
}
