<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$moduleclassSfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$locale = str_replace('-', '_', Factory::getLanguage()->getTag());
$timezone = $calendar['timezone'] ?? 'UTC';
$dateStyle = (string) $params->get('date_format', 'full') === 'short' ? IntlDateFormatter::SHORT : IntlDateFormatter::FULL;
$dateFormatter = new IntlDateFormatter($locale, $dateStyle, IntlDateFormatter::NONE, $timezone);
$timeFormatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::SHORT, $timezone);
$presentation = $calendar['presentation'] ?? 'agenda';
$groupDates = $presentation === 'month' || ($presentation !== 'list' && (int) $params->get('group_by_date', 1) === 1);
$imageSize = min(96, max(16, (int) $params->get('image_size', 24)));
?>
<div class="mod-joomleague-calendar<?php echo $moduleclassSfx; ?>">
	<?php if ($presentation === 'month') require __DIR__ . '/month.php'; ?>
	<?php if (isset($calendar['error'])) : ?>
		<?php if ((int) $params->get('show_empty', 1) === 1) : ?><div class="alert alert-info mb-0"><?php echo Text::_($calendar['error']); ?></div><?php endif; ?>
	<?php else : ?>
		<?php foreach ($calendar['groups'] as $group) : ?>
			<section class="mb-3" id="jl-calendar-<?php echo (int) ($calendar['module_id'] ?? 0); ?>-<?php echo htmlspecialchars($group['date'], ENT_QUOTES, 'UTF-8'); ?>">
				<?php if ($groupDates) : ?><h3 class="h6 border-bottom pb-2 mb-0"><?php echo htmlspecialchars((string) $dateFormatter->format(new DateTimeImmutable($group['date'] . ' 12:00:00', new DateTimeZone($timezone))), ENT_QUOTES, 'UTF-8'); ?></h3><?php endif; ?>
				<div class="list-group list-group-flush">
					<?php foreach ($group['items'] as $item) : ?>
						<?php $tag = $item['show_detail'] ? 'a' : 'div'; ?>
						<<?php echo $tag; ?> class="list-group-item<?php echo $item['show_detail'] ? ' list-group-item-action' : ''; ?> px-0"<?php echo $item['show_detail'] ? ' href="' . htmlspecialchars(Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $item['id']), ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
							<div class="d-flex justify-content-between gap-3">
								<div style="min-width:0">
									<?php if ((int) $params->get('show_project', 1) === 1) : ?><div class="small text-body-secondary"><?php echo htmlspecialchars((string) $item['project_name'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
									<?php if (!$groupDates && (int) $params->get('show_date', 1) === 1) : ?><div class="small"><?php echo htmlspecialchars((string) $dateFormatter->format((int) $item['timestamp']), ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
									<?php if ((int) $params->get('show_stage', 0) === 1 && !empty($item['stage_name'])) : ?><div class="small text-body-secondary"><?php echo htmlspecialchars((string) $item['stage_name'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
									<?php if ((int) $params->get('show_participants', 1) === 1) : ?><div class="fw-semibold text-break">
										<?php foreach ($item['participants'] as $index => $participant) : ?>
											<?php if ($index > 0) : ?><span class="text-body-secondary mx-1">·</span><?php endif; ?>
											<?php $image = (string) ($participant['image'] ?? ''); if ((int) $params->get('show_images', 0) === 1 && $image !== '' && (preg_match('~^https?://~i', $image) || (!str_contains($image, ':') && !str_starts_with($image, '//')))) : ?><img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="<?php echo $imageSize; ?>" height="<?php echo $imageSize; ?>" class="object-fit-contain me-1" loading="lazy"><?php endif; ?>
											<?php echo htmlspecialchars((string) $participant['name'], ENT_QUOTES, 'UTF-8'); ?>
											<?php if ((int) $params->get('show_result', 1) === 1 && $participant['score'] !== null) : ?><span class="badge text-bg-secondary"><?php echo htmlspecialchars((string) $participant['score'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
										<?php endforeach; ?>
									</div><?php endif; ?>
									<?php if ((int) $params->get('show_venue', 1) === 1 && $item['venue_name']) : ?><div class="small text-body-secondary"><span class="icon-location" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $item['venue_name'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
									<?php if ((int) $params->get('show_status', 0) === 1) : $status = !empty($item['played']) ? 'completed' : (string) ($item['status_code'] ?? 'scheduled'); $status = in_array($status, ['scheduled', 'completed', 'cancelled', 'postponed', 'live'], true) ? $status : 'scheduled'; ?><div class="small text-body-secondary"><?php echo Text::_('MOD_JOOMLEAGUE_CALENDAR_STATUS_' . strtoupper($status)); ?></div><?php endif; ?>
								</div>
								<?php if ((int) $params->get('show_time', 1) === 1) : ?><time class="text-nowrap" datetime="<?php echo gmdate('Y-m-d\TH:i:s\Z', (int) $item['timestamp']); ?>"><?php echo htmlspecialchars((string) $timeFormatter->format((int) $item['timestamp']), ENT_QUOTES, 'UTF-8'); ?></time><?php endif; ?>
							</div>
						</<?php echo $tag; ?>>
						<?php if ((int) $params->get('show_calendar', 0) === 1) : ?><a class="small d-inline-block mb-2" href="<?php echo htmlspecialchars(Route::_('index.php?option=com_joomleague&task=ical.download&scope=event&project_id=' . (int) $item['project_id'] . '&event_id=' . (int) $item['id']), ENT_QUOTES, 'UTF-8'); ?>"><span class="icon-calendar me-1" aria-hidden="true"></span><?php echo Text::_('MOD_JOOMLEAGUE_CALENDAR_EXPORT'); ?></a><?php endif; ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
