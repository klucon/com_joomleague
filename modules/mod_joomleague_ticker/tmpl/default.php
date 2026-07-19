<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_ticker
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                      $list    Match rows.
 * @var  \Joomla\Registry\Registry  $params  Module parameters.
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

\defined('_JEXEC') or die;

if (empty($list)) {
    echo '<div class="jl-module jl-ticker-empty border rounded-2 bg-body-tertiary text-body p-2 small">' . Text::_('MOD_JOOMLEAGUE_TICKER_NO_DATA') . '</div>';

    return;
}

$dateFormat = (string) $params->get('dateformat', 'D, M Y H:i');
$dateFormat = str_contains($dateFormat, '%') ? str_replace(['%d', '%m', '%Y', '%y', '%H', '%M', '%S'], ['d', 'm', 'Y', 'y', 'H', 'i', 's'], $dateFormat) : $dateFormat;
$showDate = (int) $params->get('showdate', 1) === 1;
$showProject = (int) $params->get('showproject', 1) === 1;
$mode = strtoupper((string) $params->get('mode', 'T') ?: 'T');
$resultSeparator = trim((string) $params->get('result_separator', $params->get('results_separator', ':'))) ?: ':';
$teamSeparator = trim((string) $params->get('team_separator', 'vs')) ?: 'vs';
$teamFormat = (int) $params->get('teamformat', 2);
$urlFormat = (int) $params->get('urlformat', 2);
$isList = $mode === 'L' || $mode === 'V';

$formatName = static function (object $match, string $side) use ($teamFormat): string {
    $prefix = $side === 'home' ? 'home' : 'away';

    return trim((string) match ($teamFormat) {
        1, 5, 8 => ($match->{$prefix . '_team_middle_name'} ?? '') ?: ($match->{$prefix . '_name'} ?? ''),
        2, 6, 9 => ($match->{$prefix . '_team_short_name'} ?? '') ?: ($match->{$prefix . '_name'} ?? ''),
        default => $match->{$prefix . '_name'} ?? '',
    });
};

$logoPath = static function (object $match, string $side): string {
    $prefix = $side === 'home' ? 'home' : 'away';
    $path = trim((string) (($match->{$prefix . '_club_logo_small'} ?? '') ?: ($match->{$prefix . '_club_logo_middle'} ?? '')));

    if ($path === '' || $path === '-1') {
        return '';
    }

    return preg_match('#^https?://#i', $path) === 1 ? $path : Uri::root(true) . '/' . ltrim($path, '/');
};

$teamMarkup = static function (object $match, string $side) use ($formatName, $logoPath, $teamFormat): string {
    $name = $formatName($match, $side);
    $logo = in_array($teamFormat, [3, 4, 5, 6, 7, 8, 9], true) ? $logoPath($match, $side) : '';
    $above = in_array($teamFormat, [7, 8, 9], true);
    $classes = $above ? 'd-inline-flex flex-column align-items-center gap-1 min-w-0' : 'd-inline-flex align-items-center gap-2 min-w-0';
    $html = '<span class="' . $classes . '">';

    if ($logo !== '') {
        $html .= '<img src="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '" alt="" loading="lazy" width="22" height="22" class="flex-shrink-0 rounded-1 object-fit-contain">';
    }

    if ($teamFormat !== 3 || $logo === '') {
        $html .= '<span class="text-truncate">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    return $html . '</span>';
};

$matchUrl = static function (object $match) use ($urlFormat): string {
    return match ($urlFormat) {
        1 => Route::_('index.php?option=com_joomleague&view=results&project_id=' . (int) ($match->project_id ?? 0) . '&round_id=' . (int) ($match->round_id ?? 0)),
        3 => Route::_('index.php?option=com_joomleague&view=schedule&project_id=' . (int) ($match->project_id ?? 0)),
        2 => Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) ($match->id ?? 0)),
        default => '',
    };
};
?>
<div class="jl-module jl-ticker <?php echo $isList ? 'jl-ticker-list-mode' : 'jl-ticker-strip-mode'; ?>">
    <style>
        .jl-ticker {
            --jl-ticker-shell-bg: linear-gradient(135deg, #f4f7fb, #ffffff);
            --jl-ticker-shell-border: var(--bs-border-color, #dee2e6);
            --jl-ticker-shell-shadow: 0 .75rem 2rem rgba(17, 40, 85, .08);
            --jl-ticker-card-bg: var(--bs-body-bg, #fff);
            --jl-ticker-card-color: var(--bs-body-color, #212529);
            --jl-ticker-card-border: var(--bs-border-color, #dee2e6);
            --jl-ticker-card-shadow: 0 .35rem 1rem rgba(17, 40, 85, .08);
            --jl-ticker-meta-color: var(--bs-secondary-color, #6c757d);
            --jl-ticker-score-bg: var(--bs-light, #f8f9fa);
            --jl-ticker-score-color: var(--bs-body-color, #212529);
            --jl-ticker-score-border: var(--bs-border-color, #dee2e6);
            --jl-ticker-fade-bg: var(--bs-body-bg, #fff);
        }
        .container-topbar .jl-ticker,
        .container-below-top .jl-ticker {
            --jl-ticker-shell-bg: transparent;
            --jl-ticker-shell-border: transparent;
            --jl-ticker-shell-shadow: none;
            --jl-ticker-card-bg: rgba(255, 255, 255, .12);
            --jl-ticker-card-color: #fff;
            --jl-ticker-card-border: rgba(255, 255, 255, .24);
            --jl-ticker-card-shadow: none;
            --jl-ticker-meta-color: rgba(255, 255, 255, .72);
            --jl-ticker-score-bg: #fff;
            --jl-ticker-score-color: #112855;
            --jl-ticker-score-border: rgba(255, 255, 255, .6);
            --jl-ticker-fade-bg: var(--cassiopeia-color-primary, #112855);
        }
        .jl-ticker-shell {
            padding: .85rem;
            background: var(--jl-ticker-shell-bg);
            border: 1px solid var(--jl-ticker-shell-border);
            border-radius: .75rem;
            box-shadow: var(--jl-ticker-shell-shadow);
        }
        .container-topbar .jl-ticker-shell,
        .container-below-top .jl-ticker-shell {
            padding: 0;
            border-radius: 0;
        }
        .jl-ticker-strip-mode .jl-ticker-track {
            display: flex;
            gap: .5rem;
            overflow-x: auto;
            padding: .15rem .1rem .3rem;
            scrollbar-width: thin;
            scroll-snap-type: x proximity;
        }
        .jl-ticker-list-mode .jl-ticker-track {
            display: grid;
            gap: .55rem;
        }
        .jl-ticker-card {
            min-width: min(17rem, 86vw);
            max-width: 100%;
            color: var(--jl-ticker-card-color) !important;
            background: var(--jl-ticker-card-bg);
            border-color: var(--jl-ticker-card-border) !important;
            box-shadow: var(--jl-ticker-card-shadow);
            scroll-snap-align: start;
        }
        .jl-ticker-card:hover,
        .jl-ticker-card:focus {
            color: var(--jl-ticker-card-color) !important;
            background: color-mix(in srgb, var(--jl-ticker-card-bg) 82%, #fff);
        }
        .jl-ticker-list-mode .jl-ticker-card {
            min-width: 0;
        }
        .jl-ticker-meta {
            color: var(--jl-ticker-meta-color);
            font-size: .75rem;
            line-height: 1.15;
        }
        .jl-ticker-teams {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: .4rem;
            font-size: .875rem;
            line-height: 1.2;
        }
        .jl-ticker-score {
            min-width: 2.85rem;
            color: var(--jl-ticker-score-color);
            background: var(--jl-ticker-score-bg);
            border-color: var(--jl-ticker-score-border) !important;
        }
        .jl-ticker-fade {
            position: relative;
        }
        .jl-ticker-strip-mode .jl-ticker-fade::after {
            content: "";
            position: absolute;
            inset: 0 0 0 auto;
            width: 2.5rem;
            pointer-events: none;
            background: linear-gradient(90deg, transparent, var(--jl-ticker-fade-bg));
        }
    </style>
    <div class="jl-ticker-shell">
        <div class="jl-ticker-track jl-ticker-fade">
            <?php foreach ($list as $match) :
                $played = $match->team1_result !== null && $match->team2_result !== null && (int) $match->count_result === 1;
                $score = $played ? ((int) $match->team1_result . $resultSeparator . (int) $match->team2_result) : $teamSeparator;
                $url = $matchUrl($match);
                $tag = $url !== '' ? 'a' : 'div';
                $attrs = $url !== '' ? ' href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' : '';
            ?>
                <<?php echo $tag; ?> class="jl-ticker-card d-block border rounded-2 p-2 text-decoration-none overflow-hidden"<?php echo $attrs; ?>>
                    <div class="jl-ticker-meta d-flex align-items-center justify-content-between gap-2 mb-1">
                        <?php if ($showDate) : ?>
                            <span class="text-truncate"><?php echo HTMLHelper::_('date', $match->match_date, $dateFormat); ?></span>
                        <?php endif; ?>
                        <?php if ($showProject && trim((string) ($match->project_name ?? '')) !== '') : ?>
                            <span class="text-truncate"><?php echo htmlspecialchars((string) $match->project_name, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="jl-ticker-teams">
                        <span class="min-w-0 text-end"><?php echo $teamMarkup($match, 'home'); ?></span>
                        <strong class="jl-ticker-score badge border px-2 py-1 text-center"><?php echo htmlspecialchars($score, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span class="min-w-0"><?php echo $teamMarkup($match, 'away'); ?></span>
                    </div>
                </<?php echo $tag; ?>>
            <?php endforeach; ?>
        </div>
    </div>
</div>
