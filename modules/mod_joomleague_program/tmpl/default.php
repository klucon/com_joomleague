<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$moduleclassSfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$formatter = new IntlDateFormatter(str_replace('-', '_', Factory::getLanguage()->getTag()), IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
?>
<div class="mod-joomleague-program<?php echo $moduleclassSfx; ?>">
	<?php if (isset($programme['error'])) : ?>
		<div class="alert alert-info mb-0"><?php echo Text::_($programme['error']); ?></div>
	<?php else : ?>
		<?php if ((int) $params->get('show_project_name', 1) === 1) : ?>
			<div class="fw-bold mb-2"><?php echo htmlspecialchars($programme['project_name'], ENT_QUOTES, 'UTF-8'); ?></div>
		<?php endif; ?>
		<div class="list-group list-group-flush">
			<?php foreach ($programme['items'] as $item) : ?>
				<a class="list-group-item list-group-item-action px-0" href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $item['id']); ?>">
					<?php if ((int) $params->get('show_round', 1) === 1 && $item['round_name'] !== '') : ?>
						<div class="small text-body-secondary mb-1"><?php echo htmlspecialchars($item['round_name'], ENT_QUOTES, 'UTF-8'); ?></div>
					<?php endif; ?>
					<div class="d-flex flex-wrap align-items-center gap-2">
						<?php foreach ($item['participants'] as $participant) : ?>
							<span class="fw-semibold"><?php echo htmlspecialchars($participant['name'], ENT_QUOTES, 'UTF-8'); ?></span>
							<?php if ((int) $params->get('show_result', 1) === 1 && $participant['score'] !== null) : ?>
								<span class="badge text-bg-secondary"><?php echo htmlspecialchars((string) $participant['score'], ENT_QUOTES, 'UTF-8'); ?></span>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
					<div class="small text-body-secondary mt-1">
						<?php if ((int) $params->get('show_date', 1) === 1 && $item['scheduled_start'] !== null) : ?>
							<span class="me-2"><span class="icon-calendar" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $formatter->format(strtotime($item['scheduled_start'] . ' UTC')), ENT_QUOTES, 'UTF-8'); ?></span>
						<?php endif; ?>
						<?php if ((int) $params->get('show_venue', 1) === 1 && $item['venue_name']) : ?>
							<span><span class="icon-location" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $item['venue_name'], ENT_QUOTES, 'UTF-8'); ?></span>
						<?php endif; ?>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
		<?php if ((int) $params->get('show_calendar', 1) === 1) :
			$scope = (string) $params->get('scope', 'project');
			$calendarUrl = 'index.php?option=com_joomleague&task=ical.download&project_id=' . (int) $params->get('project_id');
			if ($scope === 'entry') {
				$calendarUrl .= '&scope=entry&entry_id=' . (int) $params->get('entry_id');
			} elseif ($scope === 'club') {
				$calendarUrl .= '&scope=club&club_id=' . (int) $params->get('club_id');
			}
		?>
			<a class="btn btn-sm btn-outline-secondary mt-2" href="<?php echo Route::_($calendarUrl); ?>">
				<span class="icon-calendar" aria-hidden="true"></span> <?php echo Text::_('MOD_JOOMLEAGUE_PROGRAM_DOWNLOAD_CALENDAR'); ?>
			</a>
		<?php endif; ?>
	<?php endif; ?>
</div>
