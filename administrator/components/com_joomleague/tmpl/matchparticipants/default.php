<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.multiselect');
$matchId = (int) $this->match->id;
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=matchparticipants&match_id=' . $matchId); ?>" method="post" name="adminForm" id="adminForm">
	<div class="container-fluid">
		<div class="alert alert-info" role="status">
			<strong><?php echo $this->escape($this->match->round_name); ?></strong>
			<span class="mx-2">/</span><?php echo $this->escape($this->match->match_number ?: Text::_('COM_JOOMLEAGUE_MATCH_UNNUMBERED')); ?>
		</div>

		<?php if ($this->locked) : ?>
			<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_MATCHPARTICIPANTS_LOCKED'); ?></div>
		<?php endif; ?>

		<h2 class="h5"><?php echo Text::_('COM_JOOMLEAGUE_MATCHPARTICIPANTS_ASSIGNED'); ?></h2>
		<div class="table-responsive">
			<table class="table table-striped align-middle">
				<thead><tr><th class="w-1"><span class="visually-hidden"><?php echo Text::_('JGLOBAL_SELECTION'); ?></span></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PARTICIPANT_LABEL'); ?></th></tr></thead>
				<tbody>
					<?php foreach ($this->assigned as $participant) : ?>
						<tr>
							<td><input class="form-check-input" type="checkbox" name="rid[]" value="<?php echo (int) $participant['id']; ?>" aria-label="<?php echo Text::_('JGLOBAL_SELECTION'); ?>" <?php echo $this->locked ? 'disabled' : ''; ?>></td>
							<th scope="row"><?php echo $this->escape($participant['name']); ?></th>
						</tr>
					<?php endforeach; ?>
					<?php if ($this->assigned === []) : ?>
						<tr><td colspan="2"><div class="alert alert-light mb-0"><?php echo Text::_('COM_JOOMLEAGUE_MATCHPARTICIPANTS_ASSIGNED_EMPTY'); ?></div></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<h2 class="h5 mt-4"><?php echo Text::_('COM_JOOMLEAGUE_MATCHPARTICIPANTS_AVAILABLE'); ?></h2>
		<div class="table-responsive">
			<table class="table table-striped align-middle">
				<thead><tr><th class="w-1"><span class="visually-hidden"><?php echo Text::_('JGLOBAL_SELECTION'); ?></span></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PARTICIPANT_LABEL'); ?></th></tr></thead>
				<tbody>
					<?php foreach ($this->available as $entry) : ?>
						<tr>
							<td><input class="form-check-input" type="checkbox" name="cid[]" value="<?php echo (int) $entry->value; ?>" aria-label="<?php echo Text::_('JGLOBAL_SELECTION'); ?>" <?php echo $this->locked ? 'disabled' : ''; ?>></td>
							<th scope="row"><?php echo $this->escape($entry->text); ?></th>
						</tr>
					<?php endforeach; ?>
					<?php if ($this->available === []) : ?>
						<tr><td colspan="2"><div class="alert alert-light mb-0"><?php echo Text::_('COM_JOOMLEAGUE_MATCHPARTICIPANTS_AVAILABLE_EMPTY'); ?></div></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<input type="hidden" name="task" value="">
		<input type="hidden" name="boxchecked" value="0">
		<input type="hidden" name="match_id" value="<?php echo $matchId; ?>">
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
