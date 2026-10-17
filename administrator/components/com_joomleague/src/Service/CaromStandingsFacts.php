<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Domain\Service;
defined('_JEXEC') or die;

/** Official board awards and playing statistics are intentionally separate. */
final class CaromStandingsFacts
{
	public function forMatch(array $match): array
	{
		$result = [];
		foreach ($match['participants'] ?? [] as $participant) {
			$id = (int) ($participant['entry_id'] ?? 0);
			if ($id < 1) continue;
			$result[$id] = ['caroms' => '0', 'innings' => '0', 'high_run' => null];
			foreach ($match['segments'] ?? [] as $segment) {
				if (($segment['code'] ?? '') !== 'duel' || ($segment['status'] ?? '') !== 'completed') continue;
				$score = $segment['values'][$id] ?? null;
				$metadata = $segment['metadata'][$id] ?? [];
				if (!is_array($metadata)) $metadata = [];
				if (!is_string($score) || preg_match('/^[0-9]{1,9}(?:\.0{1,9})?$/D', $score) !== 1) {
					$result[$id]['caroms'] = null;
					$result[$id]['innings'] = null;
					continue;
				}
				if ($result[$id]['caroms'] !== null) $result[$id]['caroms'] = StandingsDecimal::add($result[$id]['caroms'], $score);
				$innings = $metadata['innings'] ?? null;
				if (!is_int($innings) || $innings < 1 || $innings > 100000) $result[$id]['innings'] = null;
				elseif ($result[$id]['innings'] !== null) $result[$id]['innings'] = StandingsDecimal::add($result[$id]['innings'], $innings);
				$run = $metadata['high_run'] ?? null;
				if (is_int($run) && $run >= 0 && StandingsDecimal::compare($run, $score) <= 0 && ($result[$id]['high_run'] === null || StandingsDecimal::compare($run, $result[$id]['high_run']) > 0)) $result[$id]['high_run'] = (string) $run;
			}
		}
		return $result;
	}
}
