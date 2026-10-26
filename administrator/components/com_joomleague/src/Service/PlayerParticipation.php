<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Domain\Service;
defined('_JEXEC') or die;

/** Participation timing, including a read-only bridge for preserved JL3 imports. */
final class PlayerParticipation
{
	public static function calculate(object $member, array $lineups, array $changes, ?float $duration, ?float $dismissal = null): array
	{
		$metadata = json_decode((string) ($member->metadata_json ?? ''), true) ?: [];
		if (($metadata['legacy_source_table'] ?? '') === 'match_player' && isset($metadata['legacy']['teamplayer_id'])) {
			return self::legacy($metadata['legacy'], $lineups, $duration, $dismissal);
		}
		if ($dismissal !== null && $duration !== null) $duration = min($duration, $dismissal);
		$started = (string) $member->lineup_status === 'starter';
		$active = $started; $from = 0.0; $minutes = 0.0; $incoming = 0; $outgoing = 0; $known = $duration !== null;
		foreach ($changes as $change) {
			$isIn = (int) $change->incoming_lineup_member_id === (int) $member->id;
			$isOut = (int) $change->outgoing_lineup_member_id === (int) $member->id;
			if (!$isIn && !$isOut) continue;
			$time = self::clock($change->clock_value, $change->clock_unit);
			if ($change->phase_sequence !== null && (int) $change->phase_sequence > 1) $known = false;
			if ($time === null || $time < $from || ($duration !== null && $time > $duration)) $known = false;
			if ($isIn) { $incoming++; if ($active) $known = false; $active = true; $from = $time ?? 0.0; }
			if ($isOut) { $outgoing++; if (!$active) $known = false; $minutes += max(0.0, ($time ?? 0.0) - $from); $active = false; }
		}
		if ($active && $duration !== null) $minutes += max(0.0, $duration - $from);
		$played = $started || $incoming > 0;
		return ['played' => $played, 'started' => (int) $started, 'in' => $incoming, 'out' => $outgoing, 'minutes' => $played && $known ? $minutes : null, 'timing_source' => 'native', 'timing_warning' => false];
	}

	private static function legacy(array $own, array $lineups, ?float $duration, ?float $dismissal): array
	{
		$started = (int) ($own['came_in'] ?? 0) === 0;
		$incoming = (int) ($own['came_in'] ?? 0) === 1;
		$minutes = $duration === null ? null : ($started ? $duration : 0.0);
		if ($incoming && array_key_exists('in_for', $own) && $own['in_for'] !== null) {
			$time = self::legacyClock($own['in_out_time'] ?? null);
			$minutes = $duration !== null && $time !== null ? $duration - $time : null;
		}
		$outgoing = (int) ($own['out'] ?? 0);
		foreach ($lineups as $other) {
			$legacy = json_decode((string) ($other->metadata_json ?? ''), true)['legacy'] ?? [];
			if ((int) ($legacy['came_in'] ?? 0) !== 1 || (int) ($legacy['in_for'] ?? 0) !== (int) $own['teamplayer_id']) continue;
			$outgoing++;
			$time = self::legacyClock($legacy['in_out_time'] ?? null);
			$minutes = $minutes !== null && $duration !== null && $time !== null ? $minutes + $time - $duration : null;
		}
		if ($dismissal !== null && $duration !== null && $minutes !== null) $minutes -= $duration - $dismissal;
		return ['played' => true, 'started' => (int) $started, 'in' => (int) $incoming, 'out' => $outgoing, 'minutes' => $minutes, 'timing_source' => 'legacy', 'timing_warning' => $minutes !== null && ($minutes < 0 || ($duration !== null && $minutes > $duration))];
	}

	public static function clock(mixed $value, ?string $unit): ?float
	{
		if (!is_numeric($value) || (float) $value < 0 || (float) $value > 86400000) return null;
		return match ($unit) { 'minute' => (float) $value, 'second' => (float) $value / 60, 'millisecond' => (float) $value / 60000, default => null };
	}

	private static function legacyClock(mixed $value): ?float
	{
		// JL3 summed numeric prefixes, including stoppage-time strings such as 90+2.
		if ($value === null || $value === '') return 0.0;
		return preg_match('/^\s*(\d+(?:\.\d+)?)/', (string) $value, $matches) ? (float) $matches[1] : null;
	}
}
