<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

final class ScheduleTemplateService
{
	private const DIRECTORY = JPATH_ADMINISTRATOR . '/components/com_joomleague/resources/schedule-templates';
	private ?array $templates = null;

	/** @return array<string,object> */
	public function all(): array
	{
		if ($this->templates !== null) return $this->templates;
		$result = [];
		foreach (glob(self::DIRECTORY . '/*.json') ?: [] as $file) {
			if (basename($file) === 'schedule-template.schema.json') continue;
			$data = file_get_contents($file);
			if ($data === false) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_ERROR_READ'));
			$template = json_decode($data, false, 512, JSON_THROW_ON_ERROR);
			$this->validate($template, basename($file));
			if (isset($result[$template->templateId])) throw new \UnexpectedValueException(Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_ERROR_DUPLICATE'));
			$template->sourceFile = basename($file); $template->checksum = hash('sha256', $data);
			$result[$template->templateId] = $template;
		}
		ksort($result, SORT_STRING);
		return $this->templates = $result;
	}

	public function get(string $id): object
	{
		$template = $this->all()[$id] ?? null;
		if (!$template) throw new \InvalidArgumentException(Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_ERROR_MISSING'));
		return $template;
	}

	public function supports(object $template, string $contestType, int $participantCount): bool
	{
		$typeCompatible = ($contestType === 'head_to_head' && $template->type === 'round-robin') || ($contestType === 'race' && $template->type === 'race');
		if (!$typeCompatible) return false;
		$counts = $template->supportedTeamCounts;
		if (isset($counts->values)) return in_array($participantCount, array_map('intval', $counts->values), true);
		return $participantCount >= (int) ($counts->min ?? 0) && $participantCount <= (int) ($counts->max ?? PHP_INT_MAX);
	}

	/** @return list<object> */
	public function rounds(object $template, int $participantCount): array
	{
		if ($template->type === 'race') return [(object) ['round' => 1, 'matches' => [(object) ['id' => 'R1M1', 'participants' => 'all']]]];
		$table = $template->tables->{(string) $participantCount} ?? null;
		if (!$table || !is_array($table->schedule ?? null)) throw new \InvalidArgumentException(Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_ERROR_COUNT'));
		return $table->schedule;
	}

	private function validate(mixed $template, string $file): void
	{
		if (!is_object($template)) throw new \UnexpectedValueException(Text::sprintf('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_ERROR_ROOT', $file));
		foreach (['templateFamily','templateId','type','generation','labelKey','descriptionKey'] as $field) if (!is_string($template->$field ?? null) || trim($template->$field) === '') throw new \UnexpectedValueException(Text::sprintf('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_ERROR_FIELD', $file, $field));
		if ($template->templateFamily !== 'schedule' || preg_match('/^[a-z][a-z0-9-]*-v[0-9]+$/', $template->templateId) !== 1 || !is_object($template->supportedTeamCounts ?? null)) throw new \UnexpectedValueException(Text::sprintf('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_ERROR_CONTRACT', $file));
		if ($template->type === 'round-robin' && !is_object($template->tables ?? null)) throw new \UnexpectedValueException(Text::sprintf('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_ERROR_BERGER', $file));
	}
}
