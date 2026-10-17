<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$config = $programme['config'];
if (isset($programme['error']) && !$config['show_empty']) return;

$moduleclassSfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$dateStyles = [
	'short' => [IntlDateFormatter::SHORT, IntlDateFormatter::SHORT],
	'medium' => [IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT],
	'long' => [IntlDateFormatter::LONG, IntlDateFormatter::SHORT],
];
[$dateStyle, $timeStyle] = $dateStyles[$config['date_format']];
$timezone = (string) Factory::getApplication()->get('offset', 'UTC');
$locale = str_replace('-', '_', Factory::getLanguage()->getTag());
$formatter = new IntlDateFormatter($locale, $dateStyle, $timeStyle, $timezone);
$dateOnlyFormatter = new IntlDateFormatter($locale, $dateStyle, IntlDateFormatter::NONE, $timezone);
$containerClass = $config['orientation'] === 'horizontal' ? 'row row-cols-1 row-cols-lg-2 g-3' : 'vstack gap-2';
$surfaceClass = match ($config['density']) {
	'compact' => 'border-bottom py-2',
	'detailed' => 'card card-body',
	default => 'border rounded p-3',
};
$groups = [];
if (!isset($programme['error'])) {
	foreach ($programme['items'] as $item) {
		$key = '__all';
		$label = '';
		if ($config['group_by'] === 'date') {
			$key = $item['scheduled_start'] === null ? '__unscheduled' : substr((string) $item['scheduled_start'], 0, 10);
			$label = $item['scheduled_start'] === null ? Text::_('MOD_JOOMLEAGUE_PROGRAM_UNSCHEDULED') : (string) $dateOnlyFormatter->format(strtotime($item['scheduled_start'] . ' UTC'));
		} elseif ($config['group_by'] === 'round') {
			$key = $item['round_name'] !== '' ? 'round-' . $item['round_id'] : '__no_round';
			$label = $item['round_name'] !== '' ? (string) $item['round_name'] : Text::_('MOD_JOOMLEAGUE_PROGRAM_NO_ROUND');
		}
		$groups[$key] ??= ['label' => $label, 'items' => []];
		$groups[$key]['items'][] = $item;
	}
}
?>
<div class="mod-joomleague-program<?php echo $moduleclassSfx; ?>" data-orientation="<?php echo $config['orientation']; ?>" data-density="<?php echo $config['density']; ?>">
	<?php if (isset($programme['error'])) : ?>
		<div class="alert alert-info mb-0"><?php echo Text::_($programme['error']); ?></div>
	<?php else : ?>
		<?php if ($config['heading'] !== '') : ?><h3 class="h5"><?php echo htmlspecialchars($config['heading'], ENT_QUOTES, 'UTF-8'); ?></h3><?php endif; ?>
		<?php if ($config['show_project_name']) : ?><div class="fw-bold mb-2"><?php echo htmlspecialchars($programme['project_name'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
		<?php foreach ($groups as $group) : ?>
			<?php if ($group['label'] !== '') : ?><h4 class="h6 mt-3 mb-2"><?php echo htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8'); ?></h4><?php endif; ?>
			<div class="<?php echo $containerClass; ?>">
				<?php foreach ($group['items'] as $item) : ?>
					<?php if ($config['orientation'] === 'horizontal') : ?><div class="col"><?php endif; ?>
					<article class="<?php echo $surfaceClass; ?> h-100">
						<div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
							<?php if ($config['show_round'] && $item['round_name'] !== '') : ?><div class="small text-body-secondary"><?php echo htmlspecialchars($item['round_name'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
							<div class="d-flex flex-wrap gap-1">
								<?php if ($config['show_status']) : $statusKey = 'MOD_JOOMLEAGUE_PROGRAM_STATUS_' . strtoupper((string) $item['status_code']); $statusLabel = Text::_($statusKey); ?>
									<span class="badge text-bg-light border"><?php echo htmlspecialchars($statusLabel === $statusKey ? (string) $item['status_code'] : $statusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
								<?php endif; ?>
								<?php if ($config['show_contest_type']) : $contestKey = 'MOD_JOOMLEAGUE_PROGRAM_CONTEST_' . strtoupper((string) $item['contest_type']); $contestLabel = Text::_($contestKey); ?>
									<span class="badge text-bg-secondary"><?php echo htmlspecialchars($contestLabel === $contestKey ? (string) $item['contest_type'] : $contestLabel, ENT_QUOTES, 'UTF-8'); ?></span>
								<?php endif; ?>
							</div>
						</div>
						<div class="vstack gap-1<?php echo $config['density'] === 'compact' ? '' : ' my-2'; ?>">
							<?php foreach ($item['participants'] as $participant) : ?>
								<div class="d-flex align-items-center gap-2">
									<?php if ($config['show_images'] && $participant['image']) : ?><img src="<?php echo htmlspecialchars((string) $participant['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="" width="<?php echo $config['image_size']; ?>" height="<?php echo $config['image_size']; ?>" class="object-fit-contain flex-shrink-0" loading="lazy"><?php elseif ($config['show_images']) : ?><span class="icon-user text-body-secondary flex-shrink-0 d-inline-flex align-items-center justify-content-center" style="width:<?php echo $config['image_size']; ?>px;height:<?php echo $config['image_size']; ?>px" aria-hidden="true"></span><?php endif; ?>
									<?php if ($config['show_participant_links']) : ?><a class="fw-semibold flex-grow-1" href="<?php echo Route::_('index.php?option=com_joomleague&view=participant&project_id=' . (int) $item['project_id'] . '&entry_id=' . (int) $participant['entry_id']); ?>"><?php echo htmlspecialchars($participant['name'], ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><span class="fw-semibold flex-grow-1"><?php echo htmlspecialchars($participant['name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
									<?php if ($config['show_result'] && $participant['score'] !== null) : ?><span class="badge text-bg-secondary"><?php echo htmlspecialchars((string) $participant['score'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="small text-body-secondary d-flex flex-wrap gap-3">
							<?php if ($config['show_date'] && $item['scheduled_start'] !== null) : ?><time datetime="<?php echo htmlspecialchars((string) $item['scheduled_start'], ENT_QUOTES, 'UTF-8'); ?>"><span class="icon-calendar" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $formatter->format(strtotime($item['scheduled_start'] . ' UTC')), ENT_QUOTES, 'UTF-8'); ?></time><?php endif; ?>
							<?php if ($config['show_venue'] && $item['venue_name']) : ?>
								<?php if ($config['show_venue_link'] && $item['venue_id']) : ?><a class="text-body-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=venue&venue_id=' . (int) $item['venue_id']); ?>"><span class="icon-location" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $item['venue_name'], ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><span><span class="icon-location" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $item['venue_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
							<?php endif; ?>
						</div>
						<?php if ($programme['show_detail']) : ?><a class="btn btn-sm btn-outline-primary mt-2" href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $item['id']); ?>"><?php echo Text::_('MOD_JOOMLEAGUE_PROGRAM_EVENT_DETAIL'); ?></a><?php endif; ?>
					</article>
					<?php if ($config['orientation'] === 'horizontal') : ?></div><?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
		<?php if ($config['show_calendar']) :
			$calendarUrl = 'index.php?option=com_joomleague&task=ical.download&project_id=' . $config['project_id'];
			if ($config['scope'] === 'entry') $calendarUrl .= '&scope=entry&entry_id=' . $config['scope_id'];
			elseif ($config['scope'] === 'club') $calendarUrl .= '&scope=club&club_id=' . $config['scope_id'];
		?>
			<a class="btn btn-sm btn-outline-secondary mt-3" href="<?php echo Route::_($calendarUrl); ?>"><span class="icon-calendar" aria-hidden="true"></span> <?php echo Text::_('MOD_JOOMLEAGUE_PROGRAM_DOWNLOAD_CALENDAR'); ?></a>
		<?php endif; ?>
	<?php endif; ?>
</div>
