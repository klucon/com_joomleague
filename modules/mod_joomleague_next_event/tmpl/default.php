<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$config = $event['config'];
if (isset($event['error']) && !$config['show_empty']) return;
$moduleclassSfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$styles = ['short' => IntlDateFormatter::SHORT, 'medium' => IntlDateFormatter::MEDIUM, 'long' => IntlDateFormatter::LONG];
$locale = str_replace('-', '_', Factory::getLanguage()->getTag());
$timezone = (string) Factory::getApplication()->get('offset', 'UTC');
$dateFormatter = new IntlDateFormatter($locale, $styles[$config['date_format']], IntlDateFormatter::NONE, $timezone);
$timeFormatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, $styles[$config['time_format']], $timezone);
$containerClass = $config['orientation'] === 'horizontal' ? 'row row-cols-1 row-cols-lg-2 g-3' : 'vstack gap-2';
$surfaceClass = match ($config['density']) { 'compact' => 'border-bottom py-2', 'detailed' => 'card card-body', default => 'border rounded p-3' };
$imageClass = match ($config['image_style']) { 'cover' => 'object-fit-cover', 'circle' => 'object-fit-cover rounded-circle', default => 'object-fit-contain' };

$items = [];
foreach ($event['upcoming'] ?? [] as $item) { $item['_section'] = 'upcoming'; $items[] = $item; }
foreach ($event['previous'] ?? [] as $item) { $item['_section'] = 'previous'; $items[] = $item; }
$groups = [];
foreach ($items as $item) {
	$key = '__all';
	$label = '';
	if ($config['group_by'] === 'type') { $key = $item['_section']; $label = Text::_($item['_section'] === 'upcoming' ? 'MOD_JOOMLEAGUE_NEXT_EVENT_SECTION_UPCOMING' : 'MOD_JOOMLEAGUE_NEXT_EVENT_SECTION_PREVIOUS'); }
	elseif ($config['group_by'] === 'date') { $key = $item['timestamp'] === null ? '__unscheduled' : (string) $dateFormatter->format($item['timestamp']); $label = $item['timestamp'] === null ? Text::_('MOD_JOOMLEAGUE_NEXT_EVENT_UNSCHEDULED') : $key; }
	elseif ($config['group_by'] === 'round') { $key = 'round-' . $item['round_id']; $label = $item['round_name'] !== '' ? (string) $item['round_name'] : Text::_('MOD_JOOMLEAGUE_NEXT_EVENT_NO_ROUND'); }
	$groups[$key] ??= ['label' => $label, 'items' => []];
	$groups[$key]['items'][] = $item;
}
?>
<div class="mod-joomleague-next-event<?php echo $moduleclassSfx; ?>" data-orientation="<?php echo $config['orientation']; ?>" data-density="<?php echo $config['density']; ?>">
	<?php if (isset($event['error'])) : ?>
		<div class="alert alert-info mb-0"><?php echo Text::_($event['error']); ?></div>
	<?php else : ?>
		<?php if ($config['heading'] !== '') : ?><h3 class="h5"><?php echo htmlspecialchars($config['heading'], ENT_QUOTES, 'UTF-8'); ?></h3><?php endif; ?>
		<?php foreach ($groups as $group) : ?>
			<?php if ($group['label'] !== '') : ?><h4 class="h6 mt-3 mb-2"><?php echo htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8'); ?></h4><?php endif; ?>
			<div class="<?php echo $containerClass; ?>">
			<?php foreach ($group['items'] as $item) : ?>
				<?php if ($config['orientation'] === 'horizontal') : ?><div class="col"><?php endif; ?>
				<article class="<?php echo $surfaceClass; ?> h-100">
					<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
						<div class="small text-body-secondary min-w-0">
							<?php if ($config['show_sport']) : ?><span class="jl-next-sport me-2"><?php echo htmlspecialchars((string) $item['sport_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
							<?php if ($config['show_season']) : ?><span class="jl-next-season me-2"><?php echo htmlspecialchars((string) $item['season_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
							<?php if ($config['show_project']) : ?><?php if ($config['show_project_link']) : ?><a class="jl-next-project" href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $item['project_id']); ?>"><?php echo htmlspecialchars((string) $item['project_name'], ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><span class="jl-next-project"><?php echo htmlspecialchars((string) $item['project_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?><?php endif; ?>
							<?php if ($config['show_stage'] && $item['stage_name'] !== '') : ?><span class="jl-next-stage d-block"><?php echo htmlspecialchars((string) $item['stage_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
							<?php if ($config['show_round'] && $item['round_name'] !== '') : ?><span class="jl-next-round d-block"><?php echo htmlspecialchars((string) $item['round_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
						</div>
						<?php if ($item['timestamp'] === null && ($config['show_date'] || $config['show_time'])) : ?><span class="small text-body-secondary"><?php echo Text::_('MOD_JOOMLEAGUE_NEXT_EVENT_UNSCHEDULED'); ?></span>
						<?php elseif ($config['show_date'] || $config['show_time']) : ?><time class="jl-next-datetime small text-body-secondary" datetime="<?php echo gmdate('c', $item['timestamp']); ?>"><span class="icon-calendar" aria-hidden="true"></span> <?php if ($config['show_date']) echo '<span class="jl-next-date">' . htmlspecialchars((string) $dateFormatter->format($item['timestamp']), ENT_QUOTES, 'UTF-8') . '</span>'; ?><?php if ($config['show_date'] && $config['show_time']) echo ' · '; ?><?php if ($config['show_time']) echo '<span class="jl-next-time">' . htmlspecialchars((string) $timeFormatter->format($item['timestamp']), ENT_QUOTES, 'UTF-8') . '</span>'; ?></time><?php endif; ?>
					</div>
					<div class="d-flex flex-wrap gap-1 mb-2">
						<?php if ($config['show_status']) : $key = 'MOD_JOOMLEAGUE_NEXT_EVENT_STATUS_' . strtoupper((string) $item['status_code']); $label = Text::_($key); ?><span class="jl-next-status badge text-bg-light border"><?php echo htmlspecialchars($label === $key ? (string) $item['status_code'] : $label, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
						<?php if ($config['show_contest_type']) : $key = 'MOD_JOOMLEAGUE_NEXT_EVENT_CONTEST_' . strtoupper((string) $item['contest_type']); $label = Text::_($key); ?><span class="jl-next-contest badge text-bg-secondary"><?php echo htmlspecialchars($label === $key ? (string) $item['contest_type'] : $label, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
					</div>
					<div class="vstack gap-1">
						<?php foreach ($item['participants'] as $participant) : $winner = $config['emphasize_winner'] && (int) ($participant['rank'] ?? 0) === 1; ?>
							<div class="d-flex align-items-center justify-content-between gap-2<?php echo $winner ? ' jl-next-winner fw-bold' : ''; ?>">
								<div class="d-flex align-items-center gap-2 min-w-0">
									<?php if ($config['show_images'] && $participant['image']) : ?><img src="<?php echo htmlspecialchars((string) $participant['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="" width="<?php echo $config['image_size']; ?>" height="<?php echo $config['image_size']; ?>" class="jl-next-image <?php echo $imageClass; ?> flex-shrink-0" loading="lazy"><?php elseif ($config['show_images']) : ?><span class="jl-next-image icon-user text-body-secondary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:<?php echo $config['image_size']; ?>px;height:<?php echo $config['image_size']; ?>px" aria-hidden="true"></span><?php endif; ?>
									<?php if ($config['show_participant_links']) : ?><a class="jl-next-participant text-truncate" href="<?php echo Route::_('index.php?option=com_joomleague&view=participant&project_id=' . (int) $item['project_id'] . '&entry_id=' . (int) $participant['entry_id']); ?>"><?php echo htmlspecialchars((string) $participant['name'], ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><span class="text-truncate"><?php echo htmlspecialchars((string) $participant['name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
								</div>
								<?php if ($item['played'] && $config['show_scores'] && $participant['score'] !== null) : ?><span class="jl-next-score badge text-bg-dark fs-6"><?php echo htmlspecialchars((string) $participant['score'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
					<?php $remaining = max(0, (int) $item['participant_count'] - count($item['participants'])); if ($remaining > 0) : ?><div class="small text-body-secondary mt-2"><?php echo Text::sprintf('MOD_JOOMLEAGUE_NEXT_EVENT_MORE_PARTICIPANTS', $remaining); ?></div><?php endif; ?>
					<?php if ($item['played'] && $config['show_scores'] && $config['show_decider'] && $item['decider'] !== null) : $values = []; foreach ($item['participants'] as $participant) $values[] = $item['decider']['values'][(int) $participant['participant_id']] ?? '-'; $key = 'COM_JOOMLEAGUE_SCORE_SEGMENT_' . strtoupper((string) $item['decider']['level_code']); ?><div class="jl-next-decider small text-body-secondary border-top mt-2 pt-2"><?php echo htmlspecialchars(Text::_($key) . ': ' . implode(':', $values), ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
					<?php if ($config['show_venue'] && $item['venue_name']) : ?><div class="jl-next-venue small mt-2"><span class="icon-location" aria-hidden="true"></span> <?php if ($config['show_venue_link'] && $item['venue_id']) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=venue&venue_id=' . (int) $item['venue_id']); ?>"><?php echo htmlspecialchars((string) $item['venue_name'], ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><?php echo htmlspecialchars((string) $item['venue_name'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></div><?php endif; ?>
					<?php if ($config['show_detail_link']) : ?><a class="jl-next-detail btn btn-sm btn-outline-primary mt-2" href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $item['id']); ?>"><?php echo Text::_('MOD_JOOMLEAGUE_NEXT_EVENT_DETAIL'); ?></a><?php endif; ?>
				</article>
				<?php if ($config['orientation'] === 'horizontal') : ?></div><?php endif; ?>
			<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
		<?php if ($config['show_calendar']) : $calendarUrl = 'index.php?option=com_joomleague&task=ical.download&project_id=' . $config['project_id']; if ($config['scope'] === 'entry') $calendarUrl .= '&scope=entry&entry_id=' . $config['scope_id']; elseif ($config['scope'] === 'club') $calendarUrl .= '&scope=club&club_id=' . $config['scope_id']; ?>
			<a class="jl-next-calendar btn btn-sm btn-outline-secondary mt-3" href="<?php echo Route::_($calendarUrl); ?>"><span class="icon-calendar" aria-hidden="true"></span> <?php echo Text::_('MOD_JOOMLEAGUE_NEXT_EVENT_DOWNLOAD_CALENDAR'); ?></a>
		<?php endif; ?>
	<?php endif; ?>
</div>
