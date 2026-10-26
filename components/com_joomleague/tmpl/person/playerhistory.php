<?php
declare(strict_types=1);
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Domain\Service\DecimalDisplayFormatter;
$playerHistory = $data['player_history'];
$escapePlayer = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$numberPlayer = static fn(mixed $value): string => DecimalDisplayFormatter::format((string) $value);
$personUrl = static fn(int $project, int $start = 0): string => Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $person->id . '&player_project_id=' . $project . '&player_limit=' . $playerHistory['limit'] . '&player_limitstart=' . $start) . '#player-statistics-title';
?>
<section class="jl-player-history mb-4" aria-labelledby="player-games-title">
<h2 id="player-games-title" class="h4"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_GAMES_TITLE'); ?></h2>
<form method="get" action="<?php echo Route::_('index.php'); ?>" class="row g-2 align-items-end mb-3">
<input type="hidden" name="option" value="com_joomleague"><input type="hidden" name="view" value="person"><input type="hidden" name="person_id" value="<?php echo (int) $person->id; ?>">
<div class="col-12 col-md-7"><label class="form-label" for="player-project"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_GAMES_PROJECT'); ?></label><select name="player_project_id" id="player-project" class="form-select"><option value="0"><?php echo Text::_('JALL'); ?></option><?php foreach ($playerHistory['projects'] as $id => $label) : ?><option value="<?php echo (int) $id; ?>"<?php echo $playerHistory['project_id'] === $id ? ' selected' : ''; ?>><?php echo $escapePlayer($label); ?></option><?php endforeach; ?></select></div>
<div class="col-8 col-md-3"><label class="form-label" for="player-limit"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_GAMES_LIMIT'); ?></label><select name="player_limit" id="player-limit" class="form-select"><?php foreach ([5, 10, 25] as $pageLimit) : ?><option value="<?php echo $pageLimit; ?>"<?php echo $playerHistory['limit'] === $pageLimit ? ' selected' : ''; ?>><?php echo $pageLimit; ?></option><?php endforeach; ?></select></div>
<div class="col-auto"><button type="submit" class="btn btn-primary"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_GAMES_APPLY'); ?></button></div>
</form>
<?php if ($playerHistory['history'] === []) : ?><p class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_GAMES_EMPTY'); ?></p><?php else : ?>
<div class="table-responsive" tabindex="0" role="region" aria-labelledby="player-games-title"><table class="table table-striped align-middle">
<thead><tr><?php foreach (['DATE','MATCH','PROJECT','STARTED','IN','OUT','MINUTES','METRICS'] as $column) : ?><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_GAMES_' . $column); ?></th><?php endforeach; ?></tr></thead><tbody>
<?php foreach ($playerHistory['history'] as $game) : ?>
<tr><td><?php if ($game['date']) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $game['match_id']); ?>"><?php echo HTMLHelper::_('date', $game['date'], Text::_('DATE_FORMAT_LC3'), $game['timezone']); ?></a><?php else : ?><?php echo Text::_('JNONE'); ?><?php endif; ?></td>
<td><?php foreach ($game['participants'] as $participant) : ?><div class="d-flex justify-content-between gap-3"><span><?php echo $escapePlayer($participant['name']); ?></span><strong><?php echo $participant['score'] !== null ? $escapePlayer($participant['score']) : '—'; ?></strong></div><?php endforeach; ?></td>
<td><?php echo $escapePlayer($game['project_name']); ?><div class="small text-body-secondary"><?php echo $escapePlayer($game['season_name']); ?></div></td>
<?php foreach (['started','in','out'] as $metric) : ?><td class="text-end"><?php echo (int) $game['participation'][$metric]; ?></td><?php endforeach; ?>
<td class="text-end"><?php echo $game['participation']['minutes'] !== null ? $numberPlayer($game['participation']['minutes']) : '—'; ?><?php if ($game['participation']['timing_warning']) : ?> <span class="icon-warning text-warning" title="<?php echo Text::_('COM_JOOMLEAGUE_PERSON_GAMES_LEGACY_TIME'); ?>" role="img" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_PERSON_GAMES_LEGACY_TIME'); ?>"></span><?php endif; ?></td>
<td><?php $hasMetric = false; foreach ($game['values'] as $code => $value) : if ((float) $value === 0.0) continue; $hasMetric = true; ?><div><?php echo $escapePlayer(Text::_($playerHistory['profiles'][$game['profile_code']]['columns'][$code]['name_key'])); ?>: <strong><?php echo $numberPlayer($value); ?></strong></div><?php endforeach; ?><?php if (!$hasMetric) : ?>—<?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
<?php if ($playerHistory['total'] > $playerHistory['limit']) : ?>
<div class="mt-3 jl-player-pagination"><?php echo $playerHistory['pagination']->getPagesLinks(); ?></div>
<p class="small text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_GAMES_RANGE', $playerHistory['start'] + 1, min($playerHistory['total'], $playerHistory['start'] + $playerHistory['limit']), $playerHistory['total']); ?></p>
<?php endif; ?>
</section>
<section class="jl-player-statistics mb-4" aria-labelledby="player-statistics-title">
<h2 id="player-statistics-title" class="h4"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_STATS_TITLE'); ?></h2>
<?php foreach ($playerHistory['profiles'] as $profileCode => $profileStats) : ?>
<h3 class="h5"><?php echo $escapePlayer(Text::_($profileStats['name_key'])); ?></h3>
<div class="table-responsive" tabindex="0" role="region" aria-labelledby="player-statistics-title"><table class="table table-striped align-middle">
<thead><tr><?php foreach (['SEASON','PROJECT','TEAM','PLAYED','STARTED','IN','OUT','MINUTES'] as $column) : ?><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_STATS_' . $column); ?></th><?php endforeach; ?><?php foreach ($profileStats['columns'] as $column) : ?><th scope="col" title="<?php echo $escapePlayer(Text::_($column['name_key'])); ?>"><?php echo $escapePlayer(Text::_($column['abbreviation_key'])); ?></th><?php endforeach; ?></tr></thead>
<tbody><?php foreach ($profileStats['projects'] as $project) : $totals = $project['totals']; ?>
<tr data-player-project="<?php echo (int) $project['project_id']; ?>" data-player-entry="<?php echo (int) $project['entry_id']; ?>">
<td><?php echo $escapePlayer($project['season_name']); ?></td><td><a href="<?php echo $personUrl($project['project_id']); ?>"><?php echo $escapePlayer($project['project_name']); ?></a></td><td><?php echo $escapePlayer($project['team_name']); ?></td>
<?php foreach (['played','started','in','out'] as $metric) : ?><td class="text-end"><?php echo (int) $totals[$metric]; ?></td><?php endforeach; ?>
<td class="text-end"><?php echo $totals['unknown_minutes'] === 0 ? $numberPlayer($totals['minutes']) : '—'; ?></td>
<?php foreach ($profileStats['columns'] as $code => $column) : ?><td class="text-end"><?php echo isset($totals['values'][$code]) ? $numberPlayer($totals['values'][$code]) : '—'; ?></td><?php endforeach; ?>
</tr><?php endforeach; ?></tbody>
<tfoot><tr><th scope="row" colspan="3"><?php echo Text::_($playerHistory['project_id'] === 0 ? 'COM_JOOMLEAGUE_PERSON_STATS_CAREER' : 'JGLOBAL_TOTAL'); ?></th><?php foreach (['played','started','in','out'] as $metric) : ?><td class="text-end fw-bold"><?php echo (int) $profileStats['totals'][$metric]; ?></td><?php endforeach; ?><td class="text-end fw-bold"><?php echo $profileStats['totals']['unknown_minutes'] === 0 ? $numberPlayer($profileStats['totals']['minutes']) : '—'; ?></td><?php foreach ($profileStats['columns'] as $code => $column) : ?><td class="text-end fw-bold"><?php echo isset($profileStats['totals']['values'][$code]) ? $numberPlayer($profileStats['totals']['values'][$code]) : '—'; ?></td><?php endforeach; ?></tr></tfoot>
</table></div>
<?php endforeach; ?>
<p class="small text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_STATS_UNKNOWN'); ?></p>
</section>
