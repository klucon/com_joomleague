<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$moduleclassSfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
?>
<div class="mod-joomleague-next-event<?php echo $moduleclassSfx; ?>">
	<?php if (isset($event['error'])) : ?>
		<div class="alert alert-info mb-0"><?php echo Text::_($event['error']); ?></div>
	<?php else :
		$item = $event['item'];
		$formatter = new IntlDateFormatter(str_replace('-', '_', Factory::getLanguage()->getTag()), IntlDateFormatter::LONG, IntlDateFormatter::SHORT);
		$scope = (string) $params->get('scope', 'project');
		$calendarUrl = 'index.php?option=com_joomleague&task=ical.download&project_id=' . (int) $params->get('project_id');
		if ($scope === 'entry') {
			$calendarUrl .= '&scope=entry&entry_id=' . (int) $params->get('entry_id');
		} elseif ($scope === 'club') {
			$calendarUrl .= '&scope=club&club_id=' . (int) $params->get('club_id');
		}
		?>
		<div class="card">
			<div class="card-body">
				<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
					<span class="badge text-bg-primary"><?php echo Text::_('MOD_JOOMLEAGUE_NEXT_EVENT_BADGE'); ?></span>
					<?php if ((int) $params->get('show_round', 1) === 1 && $item['round_name'] !== '') : ?>
						<span class="small text-body-secondary"><?php echo htmlspecialchars($item['round_name'], ENT_QUOTES, 'UTF-8'); ?></span>
					<?php endif; ?>
				</div>
				<?php if ((int) $params->get('show_project_name', 1) === 1) : ?>
					<div class="fw-bold mb-2"><?php echo htmlspecialchars($item['project_name'], ENT_QUOTES, 'UTF-8'); ?></div>
				<?php endif; ?>
				<a class="d-flex flex-wrap gap-2 text-decoration-none mb-2" href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $item['id']); ?>">
					<?php foreach ($item['participants'] as $participant) : ?>
						<span class="badge text-bg-light border"><?php echo htmlspecialchars($participant['name'], ENT_QUOTES, 'UTF-8'); ?></span>
					<?php endforeach; ?>
				</a>
				<div class="small text-body-secondary">
					<span class="me-2"><span class="icon-calendar" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $formatter->format(strtotime($item['scheduled_start'] . ' UTC')), ENT_QUOTES, 'UTF-8'); ?></span>
					<?php if ((int) $params->get('show_venue', 1) === 1 && $item['venue_name']) : ?>
						<span><span class="icon-location" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $item['venue_name'], ENT_QUOTES, 'UTF-8'); ?></span>
					<?php endif; ?>
				</div>
				<?php if ((int) $params->get('show_calendar', 1) === 1) : ?>
					<a class="btn btn-sm btn-outline-secondary mt-3" href="<?php echo Route::_($calendarUrl); ?>">
						<span class="icon-calendar" aria-hidden="true"></span> <?php echo Text::_('MOD_JOOMLEAGUE_NEXT_EVENT_DOWNLOAD_CALENDAR'); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
