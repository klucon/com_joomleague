<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\IcalendarBuilder;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeScopeResolver;

final class IcalModel extends BaseDatabaseModel
{
	/** @return array{content:string,filename:string}|array{error:string} */
	public function getCalendar(): array
	{
		$input = Factory::getApplication()->getInput();
		$projectId = $input->getInt('project_id', 0);
		$scope = $input->getCmd('scope', 'project');
		$scope = in_array($scope, ['project', 'entry', 'club', 'event'], true) ? $scope : 'project';
		$scopeId = match ($scope) {
			'entry' => $input->getInt('entry_id', 0),
			'club' => $input->getInt('club_id', 0),
			'event' => $input->getInt('event_id', 0),
			default => 0,
		};
		if ($projectId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_ICAL_NO_PROJECT'];
		}

		Factory::getApplication()->bootComponent('com_joomleague');
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$viewLevels = Factory::getApplication()->getIdentity()->getAuthorisedViewLevels();
		$entryIds = $scope === 'event' ? null : (new ProgrammeScopeResolver($db))->resolve($projectId, $scope, $scopeId, $viewLevels);
		if ($scope !== 'project' && $scope !== 'event' && $entryIds === []) {
			return ['error' => 'COM_JOOMLEAGUE_ICAL_SCOPE_UNAVAILABLE'];
		}

		$events = (new ProgrammeReader($db))->forProject($projectId, $entryIds, $viewLevels);
		if ($scope === 'event') {
			$events = array_values(array_filter($events, static fn (array $event): bool => (int) $event['id'] === $scopeId));
		}
		if ($events === []) {
			return ['error' => 'COM_JOOMLEAGUE_ICAL_EMPTY'];
		}

		$projectName = (string) $events[0]['project_name'];
		$urlPrefix = rtrim(Uri::root(), '/') . '/index.php?option=com_joomleague&view=eventreport&event_id';

		return [
			'content' => (new IcalendarBuilder())->build($projectName, $events, $urlPrefix),
			'filename' => $scope === 'event' ? 'joomleague-event-' . $scopeId . '.ics' : 'joomleague-program-' . $projectId . '.ics',
		];
	}
}
