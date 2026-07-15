<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$projectId = (int) $this->project->id;
$style = Uri::root(true) . '/media/com_joomleague/css/dashboard.css?v=0.13.9';
$supportedTemplateIds = ['round-robin-first-half-v1', 'round-robin-second-half-v1'];

?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
<form action="<?php echo Route::_('index.php?option=com_joomleague&task=schedule.generate'); ?>" method="post" class="form-validate">
	<div class="com-joomleague-dashboard com-joomleague-workflow">
		<div class="jl-section-panel mb-4">
			<span class="jl-section-panel__icon icon-refresh" aria-hidden="true"></span>
			<div class="jl-section-panel__content">
				<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_CONTEXT'); ?></p>
				<h2 class="h4 mb-1"><?php echo Text::_('COM_JOOMLEAGUE_GENERATE_SCHEDULE'); ?></h2>
				<p class="mb-2"><?php echo $this->escape($this->project->name); ?></p>
				<a class="jl-section-back" href="<?php echo Route::_('index.php?option=com_joomleague&view=projectpanel&project_id=' . $projectId); ?>">
					<span class="icon-arrow-left" aria-hidden="true"></span>
					<?php echo Text::_('COM_JOOMLEAGUE_BACK_TO_PROJECT_PANEL'); ?>
				</a>
			</div>
		</div>

		<div class="card">
			<div class="card-body">
				<div class="alert alert-info">
					<span class="icon-info-circle" aria-hidden="true"></span>
					<?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_WARNING'); ?>
				</div>

				<?php if ($this->templates !== []) : ?>
					<div class="mb-4">
						<h3 class="h5"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATES_AVAILABLE'); ?></h3>
						<p class="text-muted mb-3"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATES_AVAILABLE_DESC'); ?></p>
						<div class="row g-3">
							<?php foreach ($this->templates as $template) : ?>
								<div class="col-12 col-lg-6">
									<div class="card h-100 border">
										<div class="card-body">
											<div class="d-flex align-items-start gap-3">
												<span class="icon-calendar text-primary mt-1" aria-hidden="true"></span>
												<div>
													<h4 class="h6 mb-1"><?php echo Text::_($template->labelKey); ?></h4>
													<p class="small text-muted mb-2"><?php echo Text::_($template->descriptionKey); ?></p>
													<span class="badge bg-light text-dark border"><?php echo $this->escape($template->templateId); ?></span>
													<?php if (in_array($template->templateId, $supportedTemplateIds, true)) : ?>
														<span class="badge bg-success"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_STATUS_SUPPORTED'); ?></span>
													<?php else : ?>
														<span class="badge bg-secondary"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_STATUS_FUTURE'); ?></span>
													<?php endif; ?>
												</div>
											</div>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<div class="jl-schedule-form">
					<div class="jl-schedule-full">
						<label class="form-label" for="jl-schedule-mode"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_MODE'); ?></label>
						<select class="form-select" name="mode" id="jl-schedule-mode">
							<option value="generate"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_ROUND_ROBIN'); ?></option>
							<option value="empty"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_EMPTY_ROUNDS'); ?></option>
						</select>
						<div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_MODE_DESC'); ?></div>
					</div>

					<div class="jl-schedule-full">
						<label class="form-label" for="jl-template-id"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_SELECT'); ?></label>
						<select class="form-select" name="template_id" id="jl-template-id">
							<?php foreach ($this->templates as $template) : ?>
								<?php if (!in_array($template->templateId, $supportedTemplateIds, true)) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<option value="<?php echo $this->escape($template->templateId); ?>">
									<?php echo Text::_($template->labelKey); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_SELECT_DESC'); ?></div>
					</div>

					<div>
						<label class="form-label" for="jl-start-date"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_FIELD_START_DATE'); ?></label>
						<input class="form-control" type="date" name="start_date" id="jl-start-date" value="<?php echo $this->escape($this->project->start_date ?: date('Y-m-d')); ?>" required>
						<div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_FIELD_START_DATE_DESCRIPTION'); ?></div>
					</div>

					<div>
						<label class="form-label" for="jl-start-time"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_FIELD_START_TIME'); ?></label>
						<input class="form-control" type="time" name="start_time" id="jl-start-time" value="<?php echo $this->escape($this->project->start_time); ?>" required>
						<div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_FIELD_START_TIME_DESCRIPTION'); ?></div>
					</div>

					<div>
						<label class="form-label" for="jl-interval"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_INTERVAL'); ?></label>
						<input class="form-control" type="number" name="interval" id="jl-interval" value="7" min="1">
						<div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_INTERVAL_DESC'); ?></div>
					</div>

					<div>
						<label class="form-label" for="jl-round-count"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_ROUND_COUNT'); ?></label>
						<input class="form-control" type="number" name="round_count" id="jl-round-count" value="1" min="1" max="200">
						<div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_ROUND_COUNT_DESC'); ?></div>
					</div>

					<div>
						<label class="form-label" for="jl-match-number"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_FIRST_NUMBER'); ?></label>
						<input class="form-control" type="number" name="match_number" id="jl-match-number" value="1" min="0">
						<div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_FIRST_NUMBER_DESC'); ?></div>
					</div>

					<div class="d-flex align-items-center">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="double_round" value="1" id="double-round">
							<label class="form-check-label" for="double-round"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_DOUBLE'); ?></label>
							<div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_SCHEDULE_DOUBLE_DESC'); ?></div>
						</div>
					</div>
				</div>

				<button class="btn btn-primary mt-4" type="submit">
					<span class="icon-refresh" aria-hidden="true"></span>
					<?php echo Text::_('COM_JOOMLEAGUE_GENERATE'); ?>
				</button>
			</div>
		</div>
	</div>

	<input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
