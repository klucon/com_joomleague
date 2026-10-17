<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$config = $latestResults['config'];
if (isset($latestResults['error']) && !$config['show_empty']) return;
$moduleclassSfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$styles = ['short' => IntlDateFormatter::SHORT, 'medium' => IntlDateFormatter::MEDIUM, 'long' => IntlDateFormatter::LONG];
$formatter = new IntlDateFormatter(str_replace('-', '_', Factory::getLanguage()->getTag()), $styles[$config['date_format']], IntlDateFormatter::NONE, (string) Factory::getApplication()->get('offset', 'UTC'));
$timeFormatter = new IntlDateFormatter(str_replace('-', '_', Factory::getLanguage()->getTag()), IntlDateFormatter::NONE, $styles[$config['time_format']], (string) Factory::getApplication()->get('offset', 'UTC'));
$containerClass = $config['orientation'] === 'horizontal' ? 'row row-cols-1 row-cols-lg-2 g-3 mx-0' : 'vstack gap-2';
$surfaceClass = match ($config['density']) { 'compact' => 'border-bottom py-2', 'detailed' => 'card card-body', default => 'border rounded p-3' };
$groups = [];
if (!isset($latestResults['error'])) foreach ($latestResults['items'] as $item) {
	$key = '__all'; $label = '';
	if ($config['group_by'] === 'date') { $key = (string) $formatter->format((int) $item['timestamp']); $label = $key; }
	elseif ($config['group_by'] === 'project') { $key = 'project-' . $item['project_id']; $label = (string) $item['project_name']; }
	elseif ($config['group_by'] === 'round') { $key = 'round-' . $item['project_id'] . '-' . $item['round_id']; $label = $item['round_name'] !== '' ? (string) $item['round_name'] : Text::_('MOD_JOOMLEAGUE_LATEST_RESULTS_NO_ROUND'); }
	$groups[$key] ??= ['label' => $label, 'items' => []];
	$groups[$key]['items'][] = $item;
}
?>
<div class="mod-joomleague-latest-results<?php echo $moduleclassSfx; ?>" data-orientation="<?php echo $config['orientation']; ?>" data-density="<?php echo $config['density']; ?>">
	<?php if (isset($latestResults['error'])) : ?><div class="alert alert-info mb-0"><?php echo Text::_($latestResults['error']); ?></div>
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
							<?php if ($config['show_sport']) : ?><span class="me-2"><?php echo htmlspecialchars((string) $item['sport_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
							<?php if ($config['show_season']) : ?><span class="me-2"><?php echo htmlspecialchars((string) $item['season_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
							<?php if ($config['show_project']) : ?><?php if ($config['show_project_link']) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $item['project_id']); ?>"><?php echo htmlspecialchars((string) $item['project_name'], ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><span><?php echo htmlspecialchars((string) $item['project_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?><?php endif; ?>
							<?php if ($config['show_stage'] && $item['stage_name'] !== '') : ?><span class="d-block"><?php echo htmlspecialchars((string) $item['stage_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
							<?php if ($config['show_round'] && $item['round_name'] !== '') : ?><span class="d-block"><?php echo htmlspecialchars((string) $item['round_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
						</div>
						<?php if ($config['show_date'] || $config['show_time']) : ?><time class="small text-body-secondary" datetime="<?php echo gmdate('c', (int) $item['timestamp']); ?>"><span class="icon-calendar" aria-hidden="true"></span> <?php if ($config['show_date']) echo htmlspecialchars((string) $formatter->format((int) $item['timestamp']), ENT_QUOTES, 'UTF-8'); ?><?php if ($config['show_date'] && $config['show_time']) echo ' · '; ?><?php if ($config['show_time']) echo htmlspecialchars((string) $timeFormatter->format((int) $item['timestamp']), ENT_QUOTES, 'UTF-8'); ?></time><?php endif; ?>
					</div>
					<div class="d-flex flex-wrap gap-1 mb-2">
						<?php if ($config['show_status']) : $key = 'MOD_JOOMLEAGUE_LATEST_RESULTS_STATUS_' . strtoupper((string) $item['status_code']); $label = Text::_($key); ?><span class="badge text-bg-light border"><?php echo htmlspecialchars($label === $key ? (string) $item['status_code'] : $label, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
						<?php if ($config['show_contest_type']) : $key = 'MOD_JOOMLEAGUE_LATEST_RESULTS_CONTEST_' . strtoupper((string) $item['contest_type']); $label = Text::_($key); ?><span class="badge text-bg-secondary"><?php echo htmlspecialchars($label === $key ? (string) $item['contest_type'] : $label, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
					</div>
					<div class="vstack gap-1">
						<?php foreach ($item['participants'] as $participant) : $winner = $config['emphasize_winner'] && (int) ($participant['rank'] ?? 0) === 1; ?>
							<div class="d-flex align-items-center justify-content-between gap-2<?php echo $winner ? ' fw-bold' : ''; ?>">
								<div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden" style="min-width:0">
									<?php if ($config['show_images'] && $participant['image']) : ?><img src="<?php echo htmlspecialchars((string) $participant['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="" width="<?php echo $config['image_size']; ?>" height="<?php echo $config['image_size']; ?>" class="object-fit-contain flex-shrink-0" loading="lazy"><?php elseif ($config['show_images']) : ?><span class="icon-user text-body-secondary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:<?php echo $config['image_size']; ?>px;height:<?php echo $config['image_size']; ?>px" aria-hidden="true"></span><?php endif; ?>
									<?php if ($config['show_participant_links']) : ?><a class="d-block text-truncate" href="<?php echo Route::_('index.php?option=com_joomleague&view=participant&project_id=' . (int) $item['project_id'] . '&entry_id=' . (int) $participant['entry_id']); ?>"><?php echo htmlspecialchars((string) $participant['name'], ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><span class="d-block text-truncate"><?php echo htmlspecialchars((string) $participant['name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
								</div>
								<?php if ($config['show_scores'] && $participant['score'] !== null) : ?><span class="badge text-bg-dark fs-6 flex-shrink-0"><?php echo htmlspecialchars((string) $participant['score'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
					<?php $remaining = max(0, (int) $item['participant_count'] - count($item['participants'])); if ($remaining > 0) : ?><div class="small text-body-secondary mt-2"><?php echo Text::sprintf('MOD_JOOMLEAGUE_LATEST_RESULTS_MORE', $remaining); ?></div><?php endif; ?>
					<?php if ($config['show_scores'] && $config['show_decider'] && $item['decider'] !== null) : $values = []; foreach ($item['participants'] as $participant) $values[] = $item['decider']['values'][(int) $participant['participant_id']] ?? '-'; $key = 'COM_JOOMLEAGUE_SCORE_SEGMENT_' . strtoupper((string) $item['decider']['level_code']); ?>
						<div class="small text-body-secondary border-top mt-2 pt-2"><?php echo htmlspecialchars(Text::_($key) . ': ' . implode(':', $values), ENT_QUOTES, 'UTF-8'); ?></div>
					<?php endif; ?>
					<?php if ($config['show_venue'] && $item['venue_name']) : ?><div class="small mt-2"><span class="icon-location" aria-hidden="true"></span> <?php if ($config['show_venue_link'] && $item['venue_id']) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=venue&venue_id=' . (int) $item['venue_id']); ?>"><?php echo htmlspecialchars((string) $item['venue_name'], ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><?php echo htmlspecialchars((string) $item['venue_name'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></div><?php endif; ?>
					<?php if ($item['show_detail']) : ?><a class="btn btn-sm btn-outline-primary mt-2" href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $item['id']); ?>"><?php echo Text::_('MOD_JOOMLEAGUE_LATEST_RESULTS_DETAIL'); ?></a><?php endif; ?>
				</article>
				<?php if ($config['orientation'] === 'horizontal') : ?></div><?php endif; ?>
			<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
