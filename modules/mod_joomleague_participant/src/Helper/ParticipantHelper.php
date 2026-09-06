<?php
declare(strict_types=1);
namespace Joomleague\Module\Participant\Site\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\ParticipantSummaryReader;
use Joomleague\Component\Joomleague\Site\Service\ProjectTemplateProvider;
final class ParticipantHelper
{
	/** @return array<string,mixed> */
	public function getSummary(Registry $params): array
	{
		$projectId = (int) $params->get('project_id', 0); $entryId = (int) $params->get('entry_id', 0);
		if ($projectId < 1 || $entryId < 1) return ['error' => 'MOD_JOOMLEAGUE_PARTICIPANT_NOT_CONFIGURED'];
		$app = Factory::getApplication(); $app->bootComponent('com_joomleague');
		$language = Factory::getLanguage(); $language->load('com_joomleague', JPATH_SITE) || $language->load('com_joomleague', JPATH_SITE . '/components/com_joomleague');
		try { $data = (new ParticipantSummaryReader(Factory::getContainer()->get(DatabaseInterface::class)))->read($projectId, $entryId, $app->getIdentity()?->getAuthorisedViewLevels() ?? [1], Factory::getDate()->format('Y-m-d')); }
		catch (\Throwable) { return ['error' => 'MOD_JOOMLEAGUE_PARTICIPANT_UNAVAILABLE']; }
		if (isset($data['error'])) return ['error' => $data['error'] === 'participant_required' ? 'MOD_JOOMLEAGUE_PARTICIPANT_NOT_CONFIGURED' : 'MOD_JOOMLEAGUE_PARTICIPANT_UNAVAILABLE'];
		$overrides = [];
		foreach (['show_personal_data', 'show_results'] as $field) {
			$value = (string) $params->get('template_' . $field, '');
			if ($value === '0' || $value === '1') $overrides[$field] = $value === '1';
		}
		try {
			$database = Factory::getContainer()->get(DatabaseInterface::class);
			$provider = new ProjectTemplateProvider($database);
			$data['template_config'] = $provider->supports($projectId, 'participant')
				? $provider->resolve($projectId, 'participant', $overrides)
				: [];
		} catch (\Throwable) {
			$data['template_config'] = [];
		}
		return $data;
	}
}
