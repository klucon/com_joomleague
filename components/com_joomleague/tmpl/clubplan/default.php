<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Clubplan\HtmlView $this */
$plan = $this->plan;
$locale = str_replace('-', '_', Factory::getLanguage()->getTag());
$formatter = new IntlDateFormatter($locale, IntlDateFormatter::LONG, IntlDateFormatter::SHORT);
$formatScore = static fn ($value): string => $value === null ? '' : rtrim(rtrim((string) $value, '0'), '.');
?>
<div class="com-joomleague-clubplan">
	<?php if (isset($plan['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($plan['error']); ?></div>
	<?php else : ?>
		<h1 class="mb-2"><?php echo htmlspecialchars((string) $plan['club']->name, ENT_QUOTES, 'UTF-8'); ?></h1>
		<p class="text-body-secondary mb-4"><?php echo htmlspecialchars((string) $plan['project_name'], ENT_QUOTES, 'UTF-8'); ?></p>

		<form class="card card-body mb-4" action="<?php echo Route::_('index.php'); ?>" method="get">
			<div class="row g-3 align-items-end">
				<div class="col-12 col-md-5">
					<label class="form-label" for="clubplan-entry"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPLAN_FILTER_ENTRY_LABEL'); ?></label>
					<select class="form-select" id="clubplan-entry" name="entry_id">
						<option value="0"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPLAN_FILTER_ENTRY_ALL'); ?></option>
						<?php foreach ($plan['entries'] as $entry) : ?>
							<option value="<?php echo (int) $entry->id; ?>"<?php echo (int) $plan['selected_entry_id'] === (int) $entry->id ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $entry->display_name, ENT_QUOTES, 'UTF-8'); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-12 col-md-4">
					<label class="form-label" for="clubplan-period"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPLAN_FILTER_PERIOD_LABEL'); ?></label>
					<select class="form-select" id="clubplan-period" name="period">
						<?php foreach (['all', 'upcoming', 'played'] as $period) : ?>
							<option value="<?php echo $period; ?>"<?php echo $plan['period'] === $period ? ' selected' : ''; ?>><?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAN_PARAMS_SCOPE_' . strtoupper($period)); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-12 col-md-3 d-flex gap-2">
					<button class="btn btn-primary" type="submit"><span class="icon-search" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_CLUBPLAN_FILTER_APPLY'); ?></button>
					<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=clubplan&project_id=' . (int) $plan['project_id'] . '&club_id=' . (int) $plan['club']->id); ?>"><span class="icon-refresh" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_CLUBPLAN_FILTER_RESET'); ?></a>
				</div>
			</div>
			<input type="hidden" name="option" value="com_joomleague">
			<input type="hidden" name="view" value="clubplan">
			<input type="hidden" name="project_id" value="<?php echo (int) $plan['project_id']; ?>">
			<input type="hidden" name="club_id" value="<?php echo (int) $plan['club']->id; ?>">
		</form>

		<?php if ($plan['events'] === []) : ?>
			<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPLAN_VIEW_EMPTY'); ?></div>
		<?php else : ?>
			<div class="list-group shadow-sm">
				<?php foreach ($plan['events'] as $event) :
					$start = $event['scheduled_start'] ? $formatter->format(strtotime((string) $event['scheduled_start'])) : Text::_('COM_JOOMLEAGUE_TEAMPLAN_UPCOMING_LABEL');
					$participantNames = array_map(static fn (array $participant): string => (string) $participant['name'], $event['participants']);
				?>
					<a class="list-group-item list-group-item-action" href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $event['id']); ?>">
						<div class="d-flex flex-wrap justify-content-between gap-2">
							<strong><?php echo htmlspecialchars(implode(' – ', $participantNames), ENT_QUOTES, 'UTF-8'); ?></strong>
							<span><?php echo htmlspecialchars((string) $start, ENT_QUOTES, 'UTF-8'); ?></span>
						</div>
						<div class="d-flex flex-wrap gap-3 small text-body-secondary mt-1">
							<?php if ($plan['show_round']) : ?><span><?php echo htmlspecialchars((string) $event['round_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
							<?php if ($plan['show_venue'] && $event['venue_name']) : ?><span><?php echo htmlspecialchars((string) $event['venue_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
							<?php if ($event['played']) : ?><span class="fw-semibold text-body"><?php echo htmlspecialchars(implode(' : ', array_map(static fn (array $participant): string => $formatScore($participant['score']), $event['participants'])), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ($plan['show_calendar']) :
			$calendarPath = 'index.php?option=com_joomleague&task=ical.download&project_id=' . (int) $plan['project_id'] . '&scope=club&club_id=' . (int) $plan['club']->id;
			$calendarHttpUrl = rtrim(Uri::root(), '/') . '/' . ltrim(Route::_($calendarPath, false), '/');
			$calendarWebcalUrl = preg_replace('#^https?://#i', 'webcal://', $calendarHttpUrl);
			$calendarName = (string) $plan['club']->name . ' - ' . (string) $plan['project_name'];
			$googleUrl = 'https://calendar.google.com/calendar/r?cid=' . rawurlencode($calendarWebcalUrl);
			$outlookParams = 'name=' . rawurlencode($calendarName) . '&url=' . rawurlencode($calendarWebcalUrl);
		?>
			<section class="card mt-4">
				<div class="card-body">
					<h2 class="h5 card-title"><span class="icon-calendar" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAN_CALENDAR_TITLE'); ?></h2>
					<p class="card-text text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPLAN_CALENDAR_DESC'); ?></p>
					<div class="d-flex flex-wrap gap-2">
						<a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars($calendarHttpUrl, ENT_QUOTES, 'UTF-8'); ?>"><span class="icon-download" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAN_CALENDAR_ICS'); ?></a>
						<a class="btn btn-outline-primary" href="<?php echo htmlspecialchars($googleUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAN_CALENDAR_GOOGLE'); ?></a>
						<a class="btn btn-outline-primary" href="<?php echo htmlspecialchars($calendarWebcalUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAN_CALENDAR_APPLE'); ?></a>
						<a class="btn btn-outline-primary" href="https://outlook.live.com/calendar/addcalendar?<?php echo htmlspecialchars($outlookParams, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAN_CALENDAR_OUTLOOK'); ?></a>
						<a class="btn btn-outline-primary" href="https://outlook.office.com/calendar/addcalendar?<?php echo htmlspecialchars($outlookParams, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAN_CALENDAR_OFFICE'); ?></a>
					</div>
				</div>
			</section>
		<?php endif; ?>
	<?php endif; ?>
</div>
