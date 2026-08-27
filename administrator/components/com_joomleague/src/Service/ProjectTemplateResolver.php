<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

final class ProjectTemplateResolver
{
	private readonly TemplateConfigResolver $resolver;

	public function __construct(
		private readonly DatabaseInterface $database,
		?TemplateDefinitionRegistry $registry = null
	) {
		$this->resolver = new TemplateConfigResolver($registry ?? new TemplateDefinitionRegistry());
	}

	/**
	 * @param array<string, mixed> $presentationOverrides
	 * @return array<string, mixed>
	 */
	public function resolve(int $projectId, string $templateCode, array $presentationOverrides = []): array
	{
		if ($projectId < 1) {
			throw new \InvalidArgumentException('A positive project ID is required.');
		}

		$profileTemplateCode = $templateCode;
		$projectTemplateCode = $templateCode;
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('v.payload_json'))
			->select($this->database->quoteName('profile_config.params_json', 'profile_params_json'))
			->select($this->database->quoteName('project_config.params_json', 'project_params_json'))
			->from($this->database->quoteName('#__joomleague_project', 'project'))
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'v') . ' ON v.id = project.profile_version_id')
			->leftJoin(
				$this->database->quoteName('#__joomleague_profile_template_config', 'profile_config')
				. ' ON profile_config.profile_version_id = project.profile_version_id'
				. ' AND profile_config.template_code = :profile_template_code'
				. ' AND profile_config.published = 1'
			)
			->leftJoin(
				$this->database->quoteName('#__joomleague_project_template_config', 'project_config')
				. ' ON project_config.project_id = project.id'
				. ' AND project_config.template_code = :project_template_code'
			)
			->where($this->database->quoteName('project.id') . ' = :project_id')
			->bind(':profile_template_code', $profileTemplateCode)
			->bind(':project_template_code', $projectTemplateCode)
			->bind(':project_id', $projectId);
		$row = $this->database->setQuery($query)->loadObject();

		if ($row === null) {
			throw new \RuntimeException(sprintf('Project %d does not exist.', $projectId));
		}

		$profilePayload = $this->decodeObject((string) $row->payload_json, 'profile payload');
		$profileOverrides = $row->profile_params_json === null
			? []
			: $this->decodeObject((string) $row->profile_params_json, 'profile template override');
		$projectOverrides = $row->project_params_json === null
			? []
			: $this->decodeObject((string) $row->project_params_json, 'project template override');

		return $this->resolver->resolveProfileTemplate(
			$templateCode,
			$profilePayload,
			$profileOverrides,
			$projectOverrides,
			$presentationOverrides
		);
	}

	/** @return array<string, mixed> */
	private function decodeObject(string $json, string $context): array
	{
		$value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($value) || array_is_list($value)) {
			throw new \UnexpectedValueException(sprintf('The %s must be a JSON object.', $context));
		}

		return $value;
	}
}
