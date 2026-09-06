<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

foreach (['UuidFactory.php', 'EntryModelValidator.php', 'MatchResultDuration.php', 'MatchStatisticRepository.php'] as $service) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;
}

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchStatisticRepository;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);

$db = $container->get(DatabaseInterface::class);
$statistics = new MatchStatisticRepository($db);
$saved = 0;
$skipped = 0;

$matchIds = array_map('intval', $db->setQuery(
	$db->getQuery(true)
		->select('match.id')
		->from($db->quoteName('#__joomleague_project_match', 'match'))
		->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . ' ON result.match_id = match.id')
		->where('match.published = 1')
		->order('match.id ASC')
)->loadColumn());

foreach ($matchIds as $matchIndex => $matchId) {
	$context = $statistics->getContext($matchId);
	if (count($context['participants']) !== 2) {
		$skipped++;
		continue;
	}

	$position = 0;
	foreach ($context['statistics'] as $statisticCode => $definition) {
		if ($position >= 6 || !in_array((string) ($definition['source'] ?? ''), ['manual', 'manual_or_import'], true)) {
			continue;
		}

		$scope = (string) ($definition['scope'] ?? '');
		if (!in_array($scope, ['team', 'group', 'person', 'participant'], true)) {
			continue;
		}

		$targets = [];
		foreach ($context['participants'] as $participant) {
			if ($scope === 'participant' || (string) $participant->entry_kind === $scope) {
				$targets[] = 'participant:' . (int) $participant->id;
			}
		}
		if (count($targets) !== 2) {
			continue;
		}

		$valueType = (string) ($definition['value_type'] ?? 'integer');
		$firstPercentage = 45 + (($matchIndex + $position) % 11);
		foreach ($targets as $targetIndex => $target) {
			$value = match ($valueType) {
				'percentage' => (string) ($targetIndex === 1 ? 100 - $firstPercentage : $firstPercentage),
				'decimal' => number_format(1.5 + $position + ($targetIndex * .2), 1, '.', ''),
				'duration' => '00:' . str_pad((string) (20 + $position + $targetIndex), 2, '0', STR_PAD_LEFT) . ':15',
				'text', 'string' => 'Demo value ' . ($position + $targetIndex + 1),
				default => (string) (3 + (($matchIndex + $position + ($targetIndex * 2)) % 12)),
			};
			$statistics->save($matchId, [
				'statistic_code' => $statisticCode,
				'target' => $target,
				'value' => $value,
				'notes' => 'Fictional profile-defined statistic.',
			], 0);
			$saved++;
		}
		$position++;
	}
}

echo "Participant statistic completion: {$saved} values saved; {$skipped} multi-participant programme items skipped.\n";
