<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

// Pozn.: WebAssetManager (useScript) v tomto view assety neemituje; document API ano.
$this->getDocument()->addScript(Uri::root(true) . '/media/com_joomleague/js/matchdata.js?v=1.0.1', [], ['defer' => true]);

$rows = $this->rows;
$minimumRows = match ($this->section) {
	'players' => max(count($rows) + 10, count($this->players) + 5, 30),
	'events', 'statistics' => max(count($rows) + 15, 30),
	default => max(count($rows) + 8, 12),
};

while (count($rows) < $minimumRows) {
	$rows[] = (object) [];
}

$select = static function (string $name, array $options, mixed $selected, string $value = 'id', string $label = 'name'): void {
	echo '<select class="form-select" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"><option value="">-</option>';
	foreach ($options as $option) {
		$optionValue = (string) $option->{$value};
		echo '<option value="' . htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8') . '"' . ((string) $selected === $optionValue ? ' selected' : '') . '>' . htmlspecialchars((string) $option->{$label}, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	echo '</select>';
};

$checked = static fn (mixed $value): string => !empty($value) ? ' checked' : '';
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="card">
		<div class="card-body">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_MATCH_' . strtoupper($this->section)); ?></h2>
			<p><?php echo $this->escape($this->match->round_name . ' · ' . $this->match->home . ' – ' . $this->match->away); ?></p>
			<?php if ($this->section === 'players') : ?>
				<p class="text-muted"><?php echo Text::_('COM_JOOMLEAGUE_MATCH_PLAYERS_HELP'); ?></p>
			<?php endif; ?>
			<div class="table-responsive">
				<table class="table" id="jl-matchdata-table" data-next-index="<?php echo count($rows); ?>">
					<thead>
						<tr>
							<?php if ($this->section === 'events') : ?>
								<th><?php echo Text::_('COM_JOOMLEAGUE_EVENTTYPE'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_TEAMS'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_PERSON'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_MATCH_SECOND_PERSON'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_MATCH_MINUTE'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_MATCH_VALUE'); ?></th>
							<?php elseif ($this->section === 'players') : ?>
								<th><?php echo Text::_('COM_JOOMLEAGUE_PERSON'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_POSITION'); ?></th>
								<th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_MATCH_CAME_IN'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_MATCH_IN_FOR'); ?></th>
								<th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_MATCH_WENT_OUT'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_MATCH_MINUTE'); ?></th>
							<?php elseif ($this->section === 'statistics') : ?>
								<th><?php echo Text::_('COM_JOOMLEAGUE_STATISTIC'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_TEAMS'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_PERSON'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_MATCH_VALUE'); ?></th>
							<?php else : ?>
								<th><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_REFEREES'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_POSITION'); ?></th>
							<?php endif; ?>
							<th class="text-center"><?php echo Text::_('JACTION_DELETE'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $i => $row) : ?>
							<tr class="jl-matchdata-row">
								<?php if ($this->section === 'events') : ?>
									<td><?php $select("rows[$i][event_type_id]", $this->types, $row->event_type_id ?? ''); ?></td>
									<td>
										<select class="form-select" name="rows[<?php echo $i; ?>][projectteam_id]">
											<option value="<?php echo (int) $this->match->projectteam1_id; ?>"><?php echo $this->escape($this->match->home); ?></option>
											<option value="<?php echo (int) $this->match->projectteam2_id; ?>" <?php echo ($row->projectteam_id ?? 0) == $this->match->projectteam2_id ? 'selected' : ''; ?>><?php echo $this->escape($this->match->away); ?></option>
										</select>
									</td>
									<td><?php $select("rows[$i][teamplayer_id]", $this->players, $row->teamplayer_id ?? ''); ?></td>
									<td><?php $select("rows[$i][teamplayer_id2]", $this->players, $row->teamplayer_id2 ?? ''); ?></td>
									<td><input class="form-control" name="rows[<?php echo $i; ?>][event_time]" value="<?php echo $this->escape($row->event_time ?? ''); ?>"></td>
									<td><input class="form-control" type="number" step="0.01" name="rows[<?php echo $i; ?>][event_sum]" value="<?php echo $this->escape((string) ($row->event_sum ?? 1)); ?>"></td>
									<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger jl-matchdata-remove" aria-label="<?php echo Text::_('JACTION_DELETE'); ?>">×</button></td>
								<?php elseif ($this->section === 'players') : ?>
									<td><?php $select("rows[$i][teamplayer_id]", $this->players, $row->teamplayer_id ?? ''); ?></td>
									<td><?php $select("rows[$i][project_position_id]", $this->positions, $row->project_position_id ?? ''); ?></td>
									<td class="text-center"><input class="form-check-input" type="checkbox" name="rows[<?php echo $i; ?>][came_in]" value="1"<?php echo $checked($row->came_in ?? 0); ?>></td>
									<td><?php $select("rows[$i][in_for]", $this->players, $row->in_for ?? ''); ?></td>
									<td class="text-center"><input class="form-check-input" type="checkbox" name="rows[<?php echo $i; ?>][out]" value="1"<?php echo $checked($row->out ?? 0); ?>></td>
									<td><input class="form-control" name="rows[<?php echo $i; ?>][in_out_time]" value="<?php echo $this->escape($row->in_out_time ?? ''); ?>"></td>
									<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger jl-matchdata-remove" aria-label="<?php echo Text::_('JACTION_DELETE'); ?>">×</button></td>
								<?php elseif ($this->section === 'statistics') : ?>
									<td><?php $select("rows[$i][statistic_id]", $this->types, $row->statistic_id ?? ''); ?></td>
									<td>
										<select class="form-select" name="rows[<?php echo $i; ?>][projectteam_id]">
											<option value="<?php echo (int) $this->match->projectteam1_id; ?>"><?php echo $this->escape($this->match->home); ?></option>
											<option value="<?php echo (int) $this->match->projectteam2_id; ?>" <?php echo ($row->projectteam_id ?? 0) == $this->match->projectteam2_id ? 'selected' : ''; ?>><?php echo $this->escape($this->match->away); ?></option>
										</select>
									</td>
									<td><?php $select("rows[$i][teamplayer_id]", $this->players, $row->teamplayer_id ?? ''); ?></td>
									<td><input class="form-control" type="number" step="0.01" name="rows[<?php echo $i; ?>][value]" value="<?php echo $this->escape((string) ($row->value ?? 0)); ?>"></td>
									<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger jl-matchdata-remove" aria-label="<?php echo Text::_('JACTION_DELETE'); ?>">×</button></td>
								<?php else : ?>
									<td><?php $select("rows[$i][project_referee_id]", $this->referees, $row->project_referee_id ?? ''); ?></td>
									<td><?php $select("rows[$i][project_position_id]", $this->positions, $row->project_position_id ?? ''); ?></td>
									<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger jl-matchdata-remove" aria-label="<?php echo Text::_('JACTION_DELETE'); ?>">×</button></td>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<div class="d-flex gap-2">
				<button class="btn btn-primary" type="submit"><?php echo Text::_('JSAVE'); ?></button>
				<button class="btn btn-outline-secondary" type="button" id="jl-matchdata-add-row"><?php echo Text::_('COM_JOOMLEAGUE_MATCH_ADD_ROW'); ?></button>
			</div>
		</div>
	</div>
	<input type="hidden" name="task" id="jl-matchdata-task" value="matchdata.save">
	<input type="hidden" name="match_id" value="<?php echo (int) $this->match->id; ?>">
	<input type="hidden" name="section" value="<?php echo $this->escape($this->section); ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
