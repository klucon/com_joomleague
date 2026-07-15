<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Form\Form;
use Joomla\Registry\Registry;

final class TemplateModel extends EntityAdminModel
{
	protected string $entityName = 'template';
	private AdministratorApplication $application;

	public function setApplication(AdministratorApplication $application): void
	{
		$this->application = $application;
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		$form = parent::getForm($data, $loadData);

		if ($form === false) {
			return false;
		}

		$form->removeField('params');

		$template = $this->resolveTemplateName($data);
		$schema = JPATH_COMPONENT_ADMINISTRATOR . '/forms/templates/' . $template . '.xml';

		if ($template !== '' && is_file($schema)) {
			$form->loadFile($schema, false);

			if ($loadData) {
				$form->bind($this->loadFormData());
			}
		}

		return $form;
	}

	protected function loadFormData(): object
	{
		$item = $this->getItem();
		$isGlobal = (bool) $this->application->getInput()->getInt('global', 0);

		// project_id = NULL u existujícího řádku znamená centrální (globální) šablonu a nesmí
		// se přepsat – doplnění z requestu/session platí jen pro nově zakládanou položku.
		if ((int) ($item->id ?? 0) < 1 && empty($item->project_id) && !$isGlobal) {
			$projectId = $this->application->getInput()->getInt('project_id', 0);
			$item->project_id = $projectId ?: (int) $this->application->getUserState('com_joomleague.templates.project_id');
		}

		$template = $this->resolveTemplateName($item);
		$params = $this->normaliseParamsForEditing($item->params ?? '');

		$item->params = $this->hasSchema($template)
			? (json_decode($params, true) ?: [])
			: $params;

		return $item;
	}

	protected function prepareTable($table): void
	{
		// project_id = NULL/'' znamená centrální (globální) šablonu – nesmí se přetypovat na 0.
		$rawProjectId = $table->project_id;
		$table->project_id = ($rawProjectId === null || $rawProjectId === '') ? null : (int) $rawProjectId;
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

		// Imported Joomla 3 template configurations are converted on their first
		// administrator edit. New and subsequently saved values are JSON only.
		$legacyParams = parse_ini_string($params, false, INI_SCANNER_RAW);

		if (is_array($legacyParams)) {
			return json_encode($legacyParams, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
		}

		return '{}';
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

		return '{}';
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

	private function resolveTemplateName(mixed $data): string
	{
		if (is_array($data) && isset($data['template'])) {
			return preg_replace('/[^a-z0-9_]/', '', strtolower((string) $data['template'])) ?: '';
		}

		if (is_object($data) && isset($data->template)) {
			return preg_replace('/[^a-z0-9_]/', '', strtolower((string) $data->template)) ?: '';
		}

		$item = $this->getItem();

		return preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($item->template ?? ''))) ?: '';
	}

	private function hasSchema(string $template): bool
	{
		return $template !== '' && is_file(JPATH_COMPONENT_ADMINISTRATOR . '/forms/templates/' . $template . '.xml');
	}
}
