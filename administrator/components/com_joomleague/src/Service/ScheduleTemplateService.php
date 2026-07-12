<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

\defined('_JEXEC') or die;

use JsonException;
use RuntimeException;

final class ScheduleTemplateService
{
	private const TEMPLATE_DIR = JPATH_ADMINISTRATOR . '/components/com_joomleague/resources/schedule-templates';

	/** @var array<string, object>|null */
	private ?array $templates = null;

	/**
	 * @return array<string, object>
	 */
	public function getTemplates(): array
	{
		if ($this->templates !== null) {
			return $this->templates;
		}

		$templates = [];

		foreach (glob(self::TEMPLATE_DIR . '/*.json') ?: [] as $file) {
			if (basename($file) === 'schedule-template.schema.json') {
				continue;
			}

			$template = $this->loadFile($file);
			$templates[$template->templateId] = $template;
		}

		ksort($templates);

		return $this->templates = $templates;
	}

	public function getTemplate(string $templateId): object
	{
		$templates = $this->getTemplates();

		if (!isset($templates[$templateId])) {
			throw new RuntimeException('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_NOT_FOUND');
		}

		return $templates[$templateId];
	}

	public function getRoundRobinSchedule(string $templateId, int $teamCount): array
	{
		$template = $this->getTemplate($templateId);

		if (($template->type ?? '') !== 'round-robin') {
			throw new RuntimeException('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_UNSUPPORTED');
		}

		if (!isset($template->tables->{$teamCount})) {
			throw new RuntimeException('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_TEAM_COUNT_UNSUPPORTED');
		}

		$table = $template->tables->{$teamCount};

		if (!isset($table->schedule) || !is_array($table->schedule)) {
			throw new RuntimeException('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_INVALID');
		}

		return $table->schedule;
	}

	private function loadFile(string $file): object
	{
		$json = file_get_contents($file);

		if ($json === false) {
			throw new RuntimeException('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_NOT_READABLE');
		}

		try {
			$template = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			throw new RuntimeException('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_INVALID_JSON');
		}

		$this->validateTemplate($template);

		return $template;
	}

	private function validateTemplate(mixed $template): void
	{
		if (!is_object($template)) {
			throw new RuntimeException('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_INVALID');
		}

		foreach (['templateId', 'type', 'generation', 'labelKey', 'descriptionKey'] as $field) {
			if (!isset($template->{$field}) || !is_string($template->{$field}) || trim($template->{$field}) === '') {
				throw new RuntimeException('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_INVALID');
			}
		}

		if (!isset($template->version) || (!is_int($template->version) && !is_float($template->version) && !is_string($template->version))) {
			throw new RuntimeException('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_INVALID');
		}
	}
}
