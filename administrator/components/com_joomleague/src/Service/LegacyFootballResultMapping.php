<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Service;
defined('_JEXEC') or die;
use Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal;

/** Pure source mapping; no database access or competition-rule inference. */
final class LegacyFootballResultMapping
{
    public static function splitMode(array $rows): ?string
    {
        $modes = [];
        foreach ($rows as $row) {
            if ((string) ($row['match_result_type'] ?? 0) !== '0' || !empty($row['alt_decision']) || !empty($row['cancel'])) continue;
            $extended = false;
            foreach (['_ot', '_so'] as $suffix) foreach ([1, 2] as $side) if (($row['team' . $side . '_result' . $suffix] ?? null) !== null && ($row['team' . $side . '_result' . $suffix] ?? '') !== '') $extended = true;
            if ($extended) continue;
            try {
                $ends = []; $sums = []; $final = [];
                foreach ([1, 2] as $side) {
                    $parts = explode(';', (string) ($row['team' . $side . '_result_split'] ?? ''));
                    if (count($parts) !== 2 || preg_match('/^[0-9]{1,21}$/D', $parts[0]) !== 1 || preg_match('/^[0-9]{1,21}$/D', $parts[1]) !== 1) throw new \InvalidArgumentException();
                    $ends[] = self::score($parts[1]);
                    $sums[] = StandingsDecimal::add(self::score($parts[0]), self::score($parts[1]));
                    $final[] = self::score($row['team' . $side . '_result'] ?? null);
                }
                $cumulative = $ends === $final; $incremental = $sums === $final;
                if ($cumulative !== $incremental) $modes[$cumulative ? 'cumulative' : 'incremental'] = true;
            } catch (\InvalidArgumentException) { continue; }
        }
        return count($modes) === 1 ? array_key_first($modes) : null;
    }

    public static function affected(array $source): bool
    {
        if (!in_array((string) ($source['match_result_type'] ?? '0'), ['', '0'], true) || !in_array((string) ($source['alt_decision'] ?? '0'), ['', '0'], true)) return true;
        foreach (['_ot', '_so'] as $suffix) foreach ([1, 2] as $side) if (($source['team' . $side . '_result' . $suffix] ?? '') !== '') {
            if (($source['team' . $side . '_result' . $suffix] ?? null) !== null) return true;
        }
        return false;
    }

    public static function score(mixed $value): string
    {
        if ((!is_int($value) && !is_float($value) && !is_string($value)) || preg_match('/^[0-9]{1,21}(?:\.0{1,9})?$/D', (string) $value) !== 1) throw new \InvalidArgumentException('Invalid legacy football score.');
        return ltrim(explode('.', (string) $value)[0], '0') ?: '0';
    }

    public static function map(array $source, string $profileCode, ?array $project = null): ?array
    {
        if (!self::affected($source)) return null;
        if ($profileCode !== 'football') throw new \InvalidArgumentException('Unsupported legacy result profile.');
        foreach (['match_result_type', 'alt_decision'] as $flag) if (isset($source[$flag]) && !is_int($source[$flag]) && !is_string($source[$flag])) throw new \InvalidArgumentException('Invalid legacy result flag type.');
        $kind = (string) (($source['match_result_type'] ?? '') === '' ? 0 : ($source['match_result_type'] ?? 0));
        $decision = (string) (($source['alt_decision'] ?? '') === '' ? 0 : ($source['alt_decision'] ?? 0));
        if (!in_array($kind, ['0', '1', '2'], true) || !in_array($decision, ['0', '1'], true)) throw new \InvalidArgumentException('Invalid legacy result flags.');
        $pair = static function (string $suffix) use ($source): ?array {
            $a = $source['team1_result' . $suffix] ?? null;
            $b = $source['team2_result' . $suffix] ?? null;
            if (($a === null || $a === '') && ($b === null || $b === '')) return null;
            return [self::score($a), self::score($b)];
        };
        $base = $pair(''); $extra = $pair('_ot'); $shootout = $pair('_so');
        $awarded = $decision === '1' ? $pair('_decision') : null;
        if ($decision === '1' && $awarded === null) throw new \InvalidArgumentException('Administrative result is missing.');
        if ($base === null && $awarded === null) throw new \InvalidArgumentException('Played result is missing.');
        $played = $extra ?? $base; $regular = $base; $regularSource = 'base';
        if ($extra !== null) {
            $regular = $base === $extra ? null : $base;
            $regularSource = $regular === null ? 'unknown' : 'base';
            $splits = [$source['team1_result_split'] ?? null, $source['team2_result_split'] ?? null];
            if (($splits[0] !== null && $splits[0] !== '') || ($splits[1] !== null && $splits[1] !== '')) {
                $mode = $project['split_mode'] ?? null;
                if ((string) ($project['game_parts'] ?? '') !== '2' || !in_array($mode, ['cumulative', 'incremental'], true)) $splits = [];
                $split = [];
                foreach ($splits as $value) {
                    if (!is_string($value) || strlen($value) > 128) throw new \InvalidArgumentException('Incomplete split scores.');
                    $parts = explode(';', $value);
                    if (count($parts) !== 2 || $parts[1] === '' || ($mode === 'incremental' && $parts[0] === '')) throw new \InvalidArgumentException('Incomplete regulation split.');
                    $total = $mode === 'incremental' ? StandingsDecimal::add(self::score($parts[0]), self::score($parts[1])) : self::score($parts[1]);
                    if ($parts[0] !== '' && \Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal::compare(self::score($parts[0]), $total) > 0) throw new \InvalidArgumentException('Decreasing split score.');
                    $split[] = $total;
                }
                if ($split !== []) {
                    if ($regular !== null && $regular !== $split) throw new \InvalidArgumentException('Conflicting regulation sources.');
                    $regular = $split; $regularSource = 'split_' . $mode;
                }
            }
            if ($regular !== null) foreach ([0, 1] as $side) if (\Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal::compare($extra[$side], $regular[$side]) < 0) throw new \InvalidArgumentException('Conflicting extra-time result.');
        }
        $reason = $source['decision_info'] ?? '';
        if ($reason === null) $reason = '';
        if (!is_string($reason) || mb_strlen($reason, 'UTF-8') > 4000) throw new \InvalidArgumentException('Invalid decision explanation.');
        $resolution = ['version'=>2,'regulation_source'=>$regularSource,'played_final'=>$played,'regulation'=>$regular,'extra_time_total'=>$extra,'shootout'=>$shootout,'awarded'=>$awarded,'decision_reason'=>$reason];
        if ($awarded !== null) return ['final'=>$awarded,'children'=>[],'outcome'=>'forfeit','resolution'=>$resolution];
        if (($kind === '1' && $extra === null) || ($kind === '2' && $shootout === null)) throw new \InvalidArgumentException('Required legacy result phase is missing.');
        // A return leg may legitimately enter extra time with a non-drawn match score.
        if (($shootout !== null && ($played[0] !== $played[1] || $shootout[0] === $shootout[1])) || ($kind === '1' && ($shootout !== null || $played[0] === $played[1]))) throw new \InvalidArgumentException('Conflicting shootout/result type.');
        $children = $regular === null ? [] : [['period', 10, $regular]];
        if ($extra !== null && $regular !== null) $children[] = ['extra_time', 20, array_map(static fn(int $side): string => \Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal::subtract($extra[$side], $regular[$side]), [0, 1])];
        if ($shootout !== null) $children[] = ['shootout', 30, $shootout];
        return ['final'=>$played,'children'=>$children,'outcome'=>'completed','resolution'=>$resolution];
    }
}
