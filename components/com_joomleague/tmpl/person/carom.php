<?php
declare(strict_types=1);
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$carom = $data['carom']; $caromFilters = $carom['filters'];
$escapeCarom = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$caromAverage = static fn (?float $value): string => $value === null ? Text::_('COM_JOOMLEAGUE_PERSON_CAROM_NOT_RECORDED') : number_format($value, 3, '.', '');
$caromUrl = static fn (int $start): string => Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $person->id
	. '&carom_project_id=' . (int) $caromFilters['project_id'] . '&carom_season_id=' . (int) $caromFilters['season_id'] . '&carom_start=' . $start) . '#person-carom-history';
?>
<section class="mb-4" aria-labelledby="person-carom-title">
	<h2 class="h4" id="person-carom-title"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAROM_TITLE'); ?></h2>
	<form method="get" action="<?php echo Route::_('index.php'); ?>" class="row g-2 align-items-end mb-3">
		<input type="hidden" name="option" value="com_joomleague"><input type="hidden" name="view" value="person"><input type="hidden" name="person_id" value="<?php echo (int) $person->id; ?>">
		<?php foreach (['project' => 'projects', 'season' => 'seasons'] as $filterName => $optionsName) : ?>
		<div class="col-12 col-md-5"><label class="form-label" for="carom-<?php echo $filterName; ?>"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAROM_' . strtoupper($filterName)); ?></label>
		<select class="form-select" id="carom-<?php echo $filterName; ?>" name="carom_<?php echo $filterName; ?>_id"><option value="0"><?php echo Text::_('JALL'); ?></option>
		<?php foreach ($caromFilters[$optionsName] as $optionId => $optionName) : ?><option value="<?php echo (int) $optionId; ?>"<?php echo (int) $caromFilters[$filterName . '_id'] === (int) $optionId ? ' selected' : ''; ?>><?php echo $escapeCarom($optionName); ?></option><?php endforeach; ?>
		</select></div>
		<?php endforeach; ?>
		<div class="col-12 col-md-2"><button type="submit" class="btn btn-primary w-100"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAROM_FILTER'); ?></button></div>
	</form>
	<div class="table-responsive"><table class="table table-striped align-middle">
		<caption><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAROM_PROJECT_SUMMARY'); ?></caption>
		<thead><tr><?php foreach (['PROJECT', 'SEASON', 'GAMES', 'WINS', 'DRAWS', 'LOSSES', 'CAROMS', 'INNINGS', 'AVERAGE', 'BEST_AVERAGE', 'HIGH_RUN'] as $column) : ?><th scope="col" class="text-wrap"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAROM_' . $column); ?></th><?php endforeach; ?></tr></thead>
		<tbody><?php foreach ($carom['projects'] as $summary) : ?><tr>
			<th scope="row"><a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $summary['project_id']); ?>"><?php echo $escapeCarom($summary['project_name']); ?></a><div class="small fw-normal text-body-secondary"><?php echo $escapeCarom($summary['competition_name']); ?></div></th>
			<td><?php echo $escapeCarom($summary['season_name']); ?></td>
			<?php foreach (['games', 'wins', 'draws', 'losses', 'caroms'] as $column) : ?><td><?php echo (int) $summary[$column]; ?></td><?php endforeach; ?>
			<td><?php echo $summary['complete'] ? (int) $summary['innings'] : Text::_('COM_JOOMLEAGUE_PERSON_CAROM_NOT_RECORDED'); ?></td>
			<td><?php echo $caromAverage($summary['average']); ?></td><td><?php echo $caromAverage($summary['best_average']); ?></td>
			<td><?php echo $summary['high_run'] === null ? Text::_('COM_JOOMLEAGUE_PERSON_CAROM_NOT_RECORDED') : (int) $summary['high_run']; ?></td>
		</tr><?php endforeach; ?></tbody>
	</table></div>
	<h3 class="h5" id="person-carom-history"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAROM_HISTORY'); ?></h3>
	<?php if ($carom['history'] === []) : ?><p><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAROM_EMPTY'); ?></p><?php else : ?>
	<div class="table-responsive"><table class="table table-striped align-middle">
		<thead><tr><?php foreach (['DATE', 'PROJECT', 'BOARD', 'OPPONENT', 'RESULT', 'OUTCOME', 'INNINGS', 'AVERAGE', 'HIGH_RUN'] as $column) : ?><th scope="col" class="text-wrap"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAROM_' . $column); ?></th><?php endforeach; ?></tr></thead>
		<tbody><?php foreach ($carom['history'] as $game) : ?><tr>
			<td><?php echo $game['scheduled_start'] ? HTMLHelper::_('date', $game['scheduled_start'], Text::_('DATE_FORMAT_LC3'), $game['timezone']) : Text::_('COM_JOOMLEAGUE_PERSON_CAROM_NOT_RECORDED'); ?></td>
			<td><?php echo $escapeCarom($game['project_name']); ?></td><td><?php echo (int) $game['sequence_number']; ?></td>
			<td><?php if ($game['opponent_id'] > 0) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $game['opponent_id']); ?>"><?php echo $escapeCarom($game['opponent_name']); ?></a><?php else : echo Text::_('COM_JOOMLEAGUE_PERSON_CAROM_PRIVATE_OPPONENT'); endif; ?></td>
			<td class="text-nowrap"><a href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $game['match_id']); ?>"><?php echo (int) $game['score']; ?>:<?php echo (int) $game['opponent_score']; ?></a></td>
			<td><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAROM_' . strtoupper($game['outcome'])); ?></td>
			<td><?php echo $game['innings'] === null ? Text::_('COM_JOOMLEAGUE_PERSON_CAROM_NOT_RECORDED') : (int) $game['innings']; ?></td>
			<td><?php echo $caromAverage($game['average']); ?></td><td><?php echo $game['high_run'] === null ? Text::_('COM_JOOMLEAGUE_PERSON_CAROM_NOT_RECORDED') : (int) $game['high_run']; ?></td>
		</tr><?php endforeach; ?></tbody>
	</table></div>
	<?php if ($carom['total'] > $carom['limit']) : ?><nav class="d-flex justify-content-between gap-2" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAROM_HISTORY'); ?>">
		<?php if ($carom['start'] > 0) : ?><a class="btn btn-outline-secondary" href="<?php echo $caromUrl(max(0, $carom['start'] - $carom['limit'])); ?>"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAROM_PREVIOUS'); ?></a><?php endif; ?>
		<span><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSON_CAROM_PAGE', $carom['start'] + 1, min($carom['start'] + $carom['limit'], $carom['total']), $carom['total']); ?></span>
		<?php if ($carom['start'] + $carom['limit'] < $carom['total']) : ?><a class="btn btn-outline-secondary" href="<?php echo $caromUrl($carom['start'] + $carom['limit']); ?>"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_CAROM_NEXT'); ?></a><?php endif; ?>
	</nav><?php endif; ?>
	<?php endif; ?>
</section>
