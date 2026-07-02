<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Date\Date;
use Joomla\Registry\Registry;

final class TemplateModel extends EntityAdminModel
{
	protected string $entityName = 'template';
	private AdministratorApplication $application;

	public function setApplication(AdministratorApplication $application): void
	{
		$this->application = $application;
	}

	protected function loadFormData(): object
	{
		$item = $this->getItem();

		if (empty($item->project_id)) {
			$projectId = $this->application->getInput()->getInt('project_id');
			$item->project_id = $projectId ?: (int) $this->application->getUserState('com_joomleague.templates.project_id');
		}

		$item->params = $this->normaliseParamsForEditing($item->params ?? '');

		return $item;
	}

	protected function prepareTable($table): void
	{
		$table->project_id = (int) $table->project_id;
		$table->template = trim((string) $table->template);
		$table->func = trim((string) $table->func);
		$table->title = trim((string) $table->title);
		$table->params = $this->normaliseParamsForStorage($table->params ?? '');
		$table->modified = (new Date())->toSql();
		$table->modified_by = (int) $this->getCurrentUser()->id ?: null;
	}

	private function normaliseParamsForEditing(mixed $params): string
	{
		$params = $this->paramsToString($params);

		if ($params === '') {
			return "{\n\n}";
		}

		if (json_validate($params)) {
			$decoded = json_decode($params, true);

			return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $params;
		}

		$decoded = @parse_ini_string($params, false, INI_SCANNER_TYPED);

		if (is_array($decoded)) {
			return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
		}

		return $params;
	}

	private function normaliseParamsForStorage(mixed $params): string
	{
		$params = $this->paramsToString($params);

		if ($params === '') {
			return '{}';
		}

		if (json_validate($params)) {
			$decoded = json_decode($params, true);

			return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $params;
		}

		return $params;
	}

	private function paramsToString(mixed $params): string
	{
		if ($params instanceof Registry) {
			$params = $params->toArray();
		}

		if (is_array($params) || is_object($params)) {
			return json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
		}

		return trim((string) $params);
	}
}
