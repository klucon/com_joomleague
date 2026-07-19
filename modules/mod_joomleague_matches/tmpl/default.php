<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_matches
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
    if ((int) $params->get('show_no_matches_notice', 0) === 1) {
        $notice = trim((string) $params->get('no_matches_notice', ''));
        echo '<div class="jl-module jl-matches-empty text-muted small">' . htmlspecialchars($notice !== '' ? $notice : Text::_('MOD_JOOMLEAGUE_MATCHES_NO_DATA'), ENT_QUOTES, 'UTF-8') . '</div>';
    }

    return;
}

$dateFormat = Text::_((string) $params->get('dateformat', 'MOD_JOOMLEAGUE_MATCHES_DATE_FORMAT_DEFAULT'));
$timeFormat = Text::_((string) $params->get('timeformat', 'MOD_JOOMLEAGUE_MATCHES_TIME_FORMAT_DEFAULT'));
$separator  = trim((string) $params->get('team_separator', ':')) ?: ':';
$showNames  = (int) $params->get('show_names', 1) === 1;
$nameMode   = (string) $params->get('team_names', 'short_name');
$showLogo   = (int) $params->get('show_picture', 1) === 1;
$logoMode   = (string) $params->get('picture_type', 'club_middle');
$linkTeams  = (int) $params->get('link_teams', 0) > 0;
$viewMap    = [
    'ranking' => 'ranking',
    'resultsrank' => 'resultsranking',
    'resultsranking' => 'resultsranking',
    'schedule' => 'schedule',
    'results' => 'results',
];

$teamName = static function (object $match, string $side) use ($nameMode): string {
    $prefix = $side === 'home' ? 'home' : 'away';

    return trim((string) match ($nameMode) {
        'name' => $match->{$prefix . '_name'} ?? '',
        'middle_name' => ($match->{$prefix . '_team_middle_name'} ?? '') ?: ($match->{$prefix . '_name'} ?? ''),
        default => ($match->{$prefix . '_team_short_name'} ?? '') ?: ($match->{$prefix . '_name'} ?? ''),
    });
};

$logoPath = static function (object $match, string $side) use ($logoMode): string {
    $prefix = $side === 'home' ? 'home' : 'away';

    $path = trim((string) match ($logoMode) {
        'club_big' => $match->{$prefix . '_club_logo_big'} ?? '',
        'club_small' => $match->{$prefix . '_club_logo_small'} ?? '',
        default => $match->{$prefix . '_club_logo_middle'} ?? '',
    });

    if ($path === '' || $path === '-1') {
        return '';
    }

    return preg_match('#^https?://#i', $path) === 1 ? $path : Uri::root(true) . '/' . ltrim($path, '/');
};

$teamMarkup = static function (object $match, string $side) use ($linkTeams, $logoPath, $showLogo, $showNames, $teamName): string {
    $name = $teamName($match, $side);
    $projectTeamId = (int) ($match->{$side . '_projectteam_id'} ?? 0);
    $logo = $showLogo ? $logoPath($match, $side) : '';
    $html = '<span class="d-inline-flex align-items-center gap-2 min-w-0">';

    if ($logo !== '') {
        $html .= '<img src="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '" alt="" loading="lazy" width="22" height="22" class="flex-shrink-0 rounded-1 object-fit-contain">';
    }

    if ($showNames || $logo === '') {
        $html .= '<span class="text-truncate">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    $html .= '</span>';

    if ($linkTeams && $projectTeamId > 0) {
        return '<a class="text-reset text-decoration-none min-w-0" href="' . Route::_('index.php?option=com_joomleague&view=team&id=' . $projectTeamId) . '">' . $html . '</a>';
    }

    return $html;
};
?>
<div class="jl-module jl-matches vstack gap-2">
    <?php foreach ($list as $match) :
        $played = $match->team1_result !== null && $match->team2_result !== null && (int) $match->count_result === 1;
        $score  = $played ? ((int) $match->team1_result . $separator . (int) $match->team2_result) : $separator;
        $reportUrl = Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $match->id);
        $projectView = $viewMap[(string) $params->get('p_link_func', 'results')] ?? 'results';
        $roundView = $viewMap[(string) $params->get('r_link_func', 'results')] ?? 'results';
        $projectUrl = Route::_('index.php?option=com_joomleague&view=' . rawurlencode($projectView) . '&project_id=' . (int) $match->project_id);
        $roundUrl = Route::_('index.php?option=com_joomleague&view=' . rawurlencode($roundView) . '&project_id=' . (int) $match->project_id . '&round_id=' . (int) $match->round_id);
    ?>
        <article class="jl-matches-item border rounded-2 p-2 bg-body-tertiary overflow-hidden">
            <?php if ((int) $params->get('show_status_notice', 0) === 1) : ?>
                <div class="small fw-semibold mb-1">
                    <?php echo htmlspecialchars((string) $params->get($played ? 'alreadyplayed_notice' : 'upcoming_notice', $played ? 'LAST MATCHES' : 'UPCOMING'), ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div class="d-flex align-items-center justify-content-between gap-2 small text-muted mb-2">
                <span class="text-truncate"><?php echo HTMLHelper::_('date', $match->match_date, trim($dateFormat . ' ' . $timeFormat)); ?></span>
                <?php if ((int) $params->get('show_matchday_title', 0) === 1 && trim((string) ($match->round_name ?? '')) !== '') : ?>
                    <?php if ((int) $params->get('link_matchday_title', 0) === 1) : ?>
                        <a class="text-muted text-decoration-none text-truncate" href="<?php echo $roundUrl; ?>"><?php echo htmlspecialchars((string) $match->round_name, ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php else : ?>
                        <span class="text-truncate"><?php echo htmlspecialchars((string) $match->round_name, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="d-grid align-items-center gap-2" style="grid-template-columns:minmax(0,1fr) auto minmax(0,1fr);">
                <div class="min-w-0 text-end"><?php echo $teamMarkup($match, 'home'); ?></div>
                <strong class="badge text-bg-light border text-body px-2 py-1"><?php echo htmlspecialchars($score, ENT_QUOTES, 'UTF-8'); ?></strong>
                <div class="min-w-0"><?php echo $teamMarkup($match, 'away'); ?></div>
            </div>

            <?php if ((int) $params->get('show_project_title', 0) === 1 || (int) $params->get('show_venue', 0) === 1 || (int) $params->get('show_spectators', 0) === 1 || (int) $params->get('show_referee', 0) === 1 || (int) $params->get('show_act_report_link', 0) === 1) : ?>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2 small text-muted">
                    <?php if ((int) $params->get('show_project_title', 0) === 1 && trim((string) ($match->project_name ?? '')) !== '') : ?>
                        <?php if ((int) $params->get('link_project_title', 0) === 1) : ?>
                            <a class="text-muted text-decoration-none" href="<?php echo $projectUrl; ?>"><?php echo htmlspecialchars((string) $match->project_name, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else : ?>
                            <span><?php echo htmlspecialchars((string) $match->project_name, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ((int) $params->get('show_venue', 0) === 1 && trim((string) ($match->playground_name ?? '')) !== '') : ?>
                        <span><?php echo htmlspecialchars((string) $params->get('venue_text', 'Venue:'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string) $match->playground_name, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>

                    <?php if ((int) $params->get('show_spectators', 0) === 1 && (int) ($match->crowd ?? 0) > 0) : ?>
                        <span><?php echo Text::_('MOD_JOOMLEAGUE_MATCHES_SPECTATORS'); ?>: <?php echo (int) $match->crowd; ?></span>
                    <?php endif; ?>

                    <?php if ((int) $params->get('show_referee', 0) === 1 && !empty($match->referees)) : ?>
                        <span><?php echo Text::_('MOD_JOOMLEAGUE_MATCHES_REFEREES'); ?>: <?php echo htmlspecialchars(implode(', ', array_map(static fn ($referee): string => trim((string) ($referee->person_name ?? '')), $match->referees)), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>

                    <?php if ((int) $params->get('show_act_report_link', 0) === 1) : ?>
                        <a class="ms-auto link-primary text-decoration-none" href="<?php echo $reportUrl; ?>"><?php echo htmlspecialchars((string) $params->get('show_act_report_text', Text::_('MOD_JOOMLEAGUE_MATCHES_REPORT')), ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
