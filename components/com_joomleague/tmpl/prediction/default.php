<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$user = Factory::getApplication()->getIdentity();
$game = $this->predictionGame;
$canTip = (int) $user->id > 0;
$selectedRoundId = (int) Factory::getApplication()->getInput()->getInt('round_id');
?>
<div class="com-joomleague-site">
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->project ? $this->escape($this->project->name) : ''; ?></div>
		<h1 class="jl-site-title"><?php echo $game ? $this->escape($game->name) : Text::_('COM_JOOMLEAGUE_SITE_PREDICTION'); ?></h1>
		<?php if ($game) : ?>
			<nav class="jl-site-nav mt-3">
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $game->project_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=schedule&project_id=' . (int) $game->project_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE'); ?></a>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=results&project_id=' . (int) $game->project_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULTS'); ?></a>
			</nav>
		<?php endif; ?>
	</section>

	<?php if (!$game) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_NOT_FOUND'); ?></div>
	<?php else : ?>
		<div class="jl-site-grid mb-4">
			<div class="jl-site-card"><strong><?php echo (int) $game->points_exact; ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_EXACT'); ?></span></div>
			<div class="jl-site-card"><strong><?php echo (int) $game->points_goal_diff; ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_GOAL_DIFF'); ?></span></div>
			<div class="jl-site-card"><strong><?php echo (int) $game->points_tendency; ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_TENDENCY'); ?></span></div>
			<div class="jl-site-card"><strong><?php echo (int) $game->deadline_minutes; ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_DEADLINE_MINUTES'); ?></span></div>
		</div>

		<div class="jl-site-panel mb-4">
			<form action="<?php echo Route::_('index.php'); ?>" method="get" class="row g-2 align-items-end mb-3">
				<input type="hidden" name="option" value="com_joomleague">
				<input type="hidden" name="view" value="prediction">
				<input type="hidden" name="game_id" value="<?php echo (int) $game->id; ?>">
				<div class="col-auto">
					<label class="form-label" for="jl-prediction-round"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROUND'); ?></label>
					<select class="form-select" id="jl-prediction-round" name="round_id">
						<option value="0"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ALL_ROUNDS'); ?></option>
						<?php foreach ($this->rounds as $round) : ?>
							<option value="<?php echo (int) $round->id; ?>" <?php echo (int) $round->id === $selectedRoundId ? 'selected' : ''; ?>><?php echo $this->escape((string) $round->name); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-auto">
					<button type="submit" class="btn btn-secondary"><?php echo Text::_('COM_JOOMLEAGUE_SITE_APPLY'); ?></button>
				</div>
			</form>
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_MY_TIPS'); ?></h2>
			<?php if (!$canTip) : ?>
				<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_LOGIN_REQUIRED'); ?></div>
			<?php endif; ?>
			<form action="<?php echo Route::_('index.php?option=com_joomleague&task=prediction.save'); ?>" method="post">
				<input type="hidden" name="game_id" value="<?php echo (int) $game->id; ?>">
				<div class="table-responsive">
					<table class="table jl-site-table align-middle">
						<thead>
							<tr>
								<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DATE'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROUND'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCHES'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_MY_TIP'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCORE'); ?></th>
								<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_POINTS'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($this->predictionMatches as $match) :
								$tip = $this->predictionTips[(int) $match->id] ?? null;
								$locked = !$canTip || !empty($match->prediction_locked) || !empty($match->prediction_played);
							?>
								<tr>
									<td><?php echo $this->escape(substr((string) $match->match_date, 0, 16)); ?></td>
									<td><?php echo $this->escape((string) $match->round_name); ?></td>
									<td>
										<a href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $match->id); ?>">
											<?php echo $this->escape((string) $match->home_name); ?> - <?php echo $this->escape((string) $match->away_name); ?>
										</a>
										<?php if (!empty($match->prediction_locked) && empty($match->prediction_played)) : ?>
											<br><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_LOCKED'); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<div class="d-flex gap-2 align-items-center">
											<input class="form-control form-control-sm" style="width:4.5rem" type="number" min="0" max="999" name="tips[<?php echo (int) $match->id; ?>][home]" value="<?php echo $tip ? (int) $tip->home_score : ''; ?>" <?php echo $locked ? 'disabled' : ''; ?>>
											<span>:</span>
											<input class="form-control form-control-sm" style="width:4.5rem" type="number" min="0" max="999" name="tips[<?php echo (int) $match->id; ?>][away]" value="<?php echo $tip ? (int) $tip->away_score : ''; ?>" <?php echo $locked ? 'disabled' : ''; ?>>
										</div>
									</td>
									<td>
										<?php echo $match->team1_result !== null && $match->team2_result !== null ? $this->escape((string) (int) $match->team1_result . ':' . (string) (int) $match->team2_result) : Text::_('COM_JOOMLEAGUE_SITE_NOT_PLAYED'); ?>
									</td>
									<td><?php echo $tip ? (int) $tip->points : 0; ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<?php if (!$this->predictionMatches) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_MATCHES'); ?></div><?php endif; ?>
				<?php if ($canTip) : ?>
					<button type="submit" class="btn btn-primary"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_SAVE'); ?></button>
				<?php endif; ?>
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		</div>

		<?php if ((int) $game->show_ranking === 1) : ?>
			<div class="jl-site-panel table-responsive">
				<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_RANKING'); ?></h2>
				<table class="table jl-site-table align-middle">
					<thead><tr><th>#</th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_TIPS'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_EXACT_HITS'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PREDICTION_TENDENCY_HITS'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_POINTS'); ?></th></tr></thead>
					<tbody>
						<?php foreach ($this->predictionRanking as $i => $row) : ?>
							<tr>
								<td><?php echo $i + 1; ?></td>
								<td><?php echo $this->escape((string) $row->user_name); ?></td>
								<td><?php echo (int) $row->tips; ?></td>
								<td><?php echo (int) $row->exact_hits; ?></td>
								<td><?php echo (int) $row->tendency_hits; ?></td>
								<td><strong><?php echo (int) $row->points; ?></strong></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php if (!$this->predictionRanking) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
