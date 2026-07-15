<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

\defined('_JEXEC') or die;

/**
 * Vykresluje sloupce tabulky pořadí podle legacy sady kódů z admin parametru
 * "ordered_columns" (viz COM_JOOMLEAGUE_FES_RANKING_PARAM_DESCR_DISPLAY_COLUMNS).
 *
 * TADMIN nemá ve v6 datovém modelu obdobu (žádná vazba tým → uživatelský účet správce),
 * proto se vykresluje jako prázdná hodnota.
 */
final class RankingColumnsHelper
{
    private const DEFAULT_CODES = ['PLAYED', 'WINS', 'TIES', 'LOSSES', 'POINTS', 'SCOREFOR', 'SCOREAGAINST', 'DIFF'];

    /** Kódy s jednoznačnou číselnou hodnotou, u kterých má smysl nabídnout klikací řazení (column_sorting). */
    public const SORTABLE_CODES = [
        'PLAYED', 'WINS', 'LOSSES', 'TIES', 'WOT', 'WSO', 'LOT', 'LSO',
        'SCOREFOR', 'SCOREAGAINST', 'SCOREPCT', 'DIFF', 'POINTS', 'BONUS',
        'START', 'LEGS_DIFF', 'LEGS_RATIO', 'WINPCT', 'QUOT', 'NEGPOINTS',
        'POINTS_RATIO', 'GFA', 'GAA', 'PPG',
    ];

    /**
     * @return  array<int, array{code: string, header: string}>
     */
    public static function resolveColumns(string $orderedColumns, string $orderedColumnsNames, string $altLegTerm = ''): array
    {
        $codes = self::parseList($orderedColumns);

        if ($codes === []) {
            $codes = self::DEFAULT_CODES;
        }

        $names = self::parseList($orderedColumnsNames);
        $columns = [];

        foreach ($codes as $index => $code) {
            $header = $names[$index] ?? self::defaultHeader($code);

            if ($altLegTerm !== '' && in_array($code, ['LEGS', 'LEGS_DIFF', 'LEGS_RATIO'], true) && !isset($names[$index])) {
                $header = match ($code) {
                    'LEGS' => $altLegTerm,
                    'LEGS_DIFF' => '+/- ' . $altLegTerm,
                    default => 'Poměr (' . $altLegTerm . ')',
                };
            }

            $columns[] = ['code' => $code, 'header' => $header];
        }

        return $columns;
    }

    /**
     * @return  array<int, string>
     */
    private static function parseList(string $raw): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn (string $v): bool => $v !== ''));
    }

    private static function defaultHeader(string $code): string
    {
        return match ($code) {
            'PLAYED' => 'Z',
            'WINS' => 'V',
            'TIES' => 'R',
            'LOSSES' => 'P',
            'WOT' => 'V po PP',
            'WSO' => 'V na NP',
            'LOT' => 'P po PP',
            'LSO' => 'P na NP',
            'SCOREFOR' => 'S',
            'SCOREAGAINST' => 'OS',
            'SCOREPCT' => '% skóre',
            'RESULTS' => 'Skóre',
            'DIFF' => '+/-',
            'POINTS' => 'B',
            'BONUS' => 'Bonus',
            'START' => 'Poč. body',
            'LEGS' => 'Sety',
            'LEGS_DIFF' => '+/- sety',
            'GB' => 'Ztráta',
            'LEGS_RATIO' => 'Poměr setů',
            'WINPCT' => '% výher',
            'QUOT' => 'Kvocient',
            'NEGPOINTS' => 'Ztr. body',
            'OLDNEGPOINTS' => 'B : ztr. b.',
            'POINTS_RATIO' => 'Poměr bodů',
            'TADMIN' => 'Správce',
            'GFA' => 'Ø vstř.',
            'GAA' => 'Ø obdrž.',
            'PPG' => 'B/zápas',
            'PPP' => '% z max.',
            'LASTGAMES' => 'Forma',
            default => strtoupper($code),
        };
    }

    /**
     * @param  array{winPoints?: int, leader?: object|null}  $context
     */
    public static function value(object $team, string $code, array $context = []): string
    {
        $winPoints = (int) ($context['winPoints'] ?? 3);
        $leader = $context['leader'] ?? null;

        return match ($code) {
            'PLAYED' => (string) $team->played,
            'WINS' => (string) $team->won,
            'LOSSES' => (string) $team->lost,
            'TIES' => (string) $team->drawn,
            'WOT' => (string) $team->cnt_wot,
            'WSO' => (string) $team->cnt_wso,
            'LOT' => (string) $team->cnt_lot,
            'LSO' => (string) $team->cnt_lso,
            'SCOREFOR' => self::formatNumber($team->goals_for),
            'SCOREAGAINST' => self::formatNumber($team->goals_against),
            'SCOREPCT' => self::formatNumber(self::percentage($team->goals_for, $team->goals_against)) . ' %',
            'RESULTS' => self::formatNumber($team->goals_for) . ':' . self::formatNumber($team->goals_against),
            'DIFF' => ($team->goal_diff > 0 ? '+' : '') . self::formatNumber($team->goal_diff),
            'POINTS' => (string) $team->points,
            'BONUS' => self::formatNumber($team->sum_bonus),
            'START' => (string) $team->start_points,
            'LEGS' => self::formatNumber($team->legs_for) . ':' . self::formatNumber($team->legs_against),
            'LEGS_DIFF' => ($team->legs_diff > 0 ? '+' : '') . self::formatNumber($team->legs_diff),
            'LEGS_RATIO' => number_format($team->legs_against > 0 ? $team->legs_for / $team->legs_against : $team->legs_for, 2),
            'WINPCT' => number_format($team->played > 0 ? $team->won / $team->played * 100 : 0.0, 1) . ' %',
            'QUOT' => number_format($team->played > 0 ? ($team->points - $team->start_points) / $team->played : 0.0, 3),
            'NEGPOINTS' => (string) $team->neg_points,
            'OLDNEGPOINTS' => $team->points . ':' . $team->neg_points,
            'POINTS_RATIO' => number_format($team->neg_points > 0 ? $team->points / $team->neg_points : (float) $team->points, 2),
            'TADMIN' => '—',
            'GFA' => number_format($team->played > 0 ? $team->goals_for / $team->played : 0.0, 2),
            'GAA' => number_format($team->played > 0 ? $team->goals_against / $team->played : 0.0, 2),
            'PPG' => number_format($team->played > 0 ? ($team->points - $team->start_points) / $team->played : 0.0, 2),
            'PPP' => number_format($team->played > 0 && $winPoints > 0 ? ($team->points / ($team->played * $winPoints)) * 100 : 0.0, 1) . ' %',
            'GB' => self::gamesBack($team, $leader),
            default => '',
        };
    }

    /**
     * Čistá číselná hodnota pro klikací řazení podle sloupce (column_sorting). Kódy, které
     * nemají jednoznačnou jednu číselnou hodnotu (RESULTS, LEGS, OLDNEGPOINTS, TADMIN, LASTGAMES, GB),
     * vracejí null a jejich záhlaví se proto nevykresluje jako odkaz.
     */
    public static function sortableValue(object $team, string $code): ?float
    {
        return match ($code) {
            'PLAYED' => (float) $team->played,
            'WINS' => (float) $team->won,
            'LOSSES' => (float) $team->lost,
            'TIES' => (float) $team->drawn,
            'WOT' => (float) $team->cnt_wot,
            'WSO' => (float) $team->cnt_wso,
            'LOT' => (float) $team->cnt_lot,
            'LSO' => (float) $team->cnt_lso,
            'SCOREFOR' => $team->goals_for,
            'SCOREAGAINST' => $team->goals_against,
            'SCOREPCT' => self::percentage($team->goals_for, $team->goals_against),
            'DIFF' => $team->goal_diff,
            'POINTS' => (float) $team->points,
            'BONUS' => $team->sum_bonus,
            'START' => (float) $team->start_points,
            'LEGS_DIFF' => $team->legs_diff,
            'LEGS_RATIO' => $team->legs_against > 0 ? $team->legs_for / $team->legs_against : $team->legs_for,
            'WINPCT' => $team->played > 0 ? $team->won / $team->played : 0.0,
            'QUOT' => $team->played > 0 ? ($team->points - $team->start_points) / $team->played : 0.0,
            'NEGPOINTS' => (float) $team->neg_points,
            'POINTS_RATIO' => $team->neg_points > 0 ? $team->points / $team->neg_points : (float) $team->points,
            'GFA' => $team->played > 0 ? $team->goals_for / $team->played : 0.0,
            'GAA' => $team->played > 0 ? $team->goals_against / $team->played : 0.0,
            'PPG' => $team->played > 0 ? ($team->points - $team->start_points) / $team->played : 0.0,
            default => null,
        };
    }

    private static function percentage(float $for, float $against): float
    {
        return $against > 0 ? $for / $against * 100 : $for * 100;
    }

    private static function gamesBack(object $team, ?object $leader): string
    {
        if ($leader === null || $leader->projectteam_id === $team->projectteam_id) {
            return '0';
        }

        $gb = (($leader->won - $team->won) - ($leader->lost - $team->lost)) / 2;

        return self::formatNumber($gb);
    }

    private static function formatNumber(float $value): string
    {
        $rounded = round($value, 1);

        return $rounded == (int) $rounded ? (string) (int) $rounded : number_format($rounded, 1);
    }
}
