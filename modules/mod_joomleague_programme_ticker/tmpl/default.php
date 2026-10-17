<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$config = $ticker['config'];
if (isset($ticker['error']) && !$config['show_empty']) return;
$items = $ticker['items'] ?? [];
$moduleclassSfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$styles = ['short' => IntlDateFormatter::SHORT, 'medium' => IntlDateFormatter::MEDIUM, 'long' => IntlDateFormatter::LONG];
$locale = str_replace('-', '_', Factory::getLanguage()->getTag());
$timezone = (string) Factory::getApplication()->get('offset', 'UTC');
$dateFormatter = new IntlDateFormatter($locale, $styles[$config['date_format']], IntlDateFormatter::NONE, $timezone);
$timeFormatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, $styles[$config['time_format']], $timezone);
$imageClass = match ($config['image_style']) { 'cover' => 'object-fit-cover', 'circle' => 'object-fit-cover rounded-circle', default => 'object-fit-contain' };
$document = Factory::getApplication()->getDocument();
$wa = $document->getWebAssetManager();
$wa->registerAndUseStyle('mod_joomleague_programme_ticker', 'modules/mod_joomleague_programme_ticker/media/css/ticker.css', [], ['version' => 'auto']);
$wa->registerAndUseScript('mod_joomleague_programme_ticker', 'modules/mod_joomleague_programme_ticker/media/js/ticker.js', [], ['version' => 'auto', 'defer' => true]);
?>
<div class="mod-joomleague-programme-ticker<?php echo $moduleclassSfx; ?>" data-joomleague-ticker data-orientation="<?php echo $config['orientation']; ?>" data-direction="<?php echo $config['direction']; ?>" data-density="<?php echo $config['density']; ?>" data-loop="<?php echo $config['loop'] ? '1' : '0'; ?>" data-pause="<?php echo $config['pause_on_hover'] ? '1' : '0'; ?>" style="--jl-ticker-duration:<?php echo $config['speed']; ?>s">
	<?php if (isset($ticker['error'])) : ?>
		<div class="alert alert-info mb-0"><?php echo Text::_($ticker['error']); ?></div>
	<?php else : ?>
		<?php if ($config['heading'] !== '') : ?><h3 class="h5 jl-ticker-heading"><?php echo htmlspecialchars($config['heading'], ENT_QUOTES, 'UTF-8'); ?></h3><?php endif; ?>
		<div class="jl-ticker-viewport" role="region" aria-label="<?php echo htmlspecialchars(Text::_('MOD_JOOMLEAGUE_PROGRAMME_TICKER_ACCESSIBLE_LABEL'), ENT_QUOTES, 'UTF-8'); ?>">
			<div class="jl-ticker-track">
			<?php foreach ($items as $index => $item) : ?>
				<?php if ($index > 0 && $config['separator'] !== '') : ?><span class="jl-ticker-separator" aria-hidden="true"><?php echo htmlspecialchars($config['separator'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
				<article class="jl-ticker-item" data-section="<?php echo $item['_section']; ?>">
					<div class="jl-ticker-context small text-body-secondary">
						<?php if ($config['show_sport']) : ?><span class="jl-ticker-sport"><?php echo htmlspecialchars((string) $item['sport_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
						<?php if ($config['show_season']) : ?><span class="jl-ticker-season"><?php echo htmlspecialchars((string) $item['season_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
						<?php if ($config['show_project']) : ?><?php if ($config['show_project_link']) : ?><a class="jl-ticker-project" href="<?php echo Route::_('index.php?option=com_joomleague&amp;view=project&amp;project_id=' . (int) $item['project_id']); ?>"><?php echo htmlspecialchars((string) $item['project_name'], ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><span class="jl-ticker-project"><?php echo htmlspecialchars((string) $item['project_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?><?php endif; ?>
						<?php if ($config['show_stage'] && $item['stage_name'] !== '') : ?><span class="jl-ticker-stage"><?php echo htmlspecialchars((string) $item['stage_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
						<?php if ($config['show_round'] && $item['round_name'] !== '') : ?><span class="jl-ticker-round"><?php echo htmlspecialchars((string) $item['round_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
					</div>
					<?php if ($config['show_date'] || $config['show_time']) : ?><time class="jl-ticker-datetime small" datetime="<?php echo gmdate('c', (int) $item['timestamp']); ?>"><?php if ($config['show_date']) : ?><span class="jl-ticker-date"><?php echo htmlspecialchars((string) $dateFormatter->format($item['timestamp']), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?><?php if ($config['show_date'] && $config['show_time']) : ?> <span aria-hidden="true">·</span> <?php endif; ?><?php if ($config['show_time']) : ?><span class="jl-ticker-time"><?php echo htmlspecialchars((string) $timeFormatter->format($item['timestamp']), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></time><?php endif; ?>
					<?php if ($config['show_status'] || $config['show_contest_type']) : ?><div class="jl-ticker-badges"><?php if ($config['show_status']) : ?><span class="jl-ticker-status badge text-bg-light border"><?php echo htmlspecialchars((string) $item['status_code'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?><?php if ($config['show_contest_type']) : ?><span class="jl-ticker-contest badge text-bg-secondary"><?php echo htmlspecialchars((string) $item['contest_type'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></div><?php endif; ?>
					<?php if ($config['show_participants']) : ?><div class="jl-ticker-participants">
					<?php foreach ($item['participants'] as $participant) : $winner = $config['emphasize_winner'] && (int) ($participant['rank'] ?? 0) === 1; ?><div class="jl-ticker-participant<?php echo $winner ? ' jl-ticker-winner fw-bold' : ''; ?>">
						<span class="d-inline-flex align-items-center gap-2 min-w-0"><?php if ($config['show_images'] && $participant['image']) : ?><img class="jl-ticker-image <?php echo $imageClass; ?>" src="<?php echo htmlspecialchars((string) $participant['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="" width="<?php echo $config['image_size']; ?>" height="<?php echo $config['image_size']; ?>" loading="lazy"><?php endif; ?><?php if ($config['show_participant_links']) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&amp;view=participant&amp;project_id=' . (int) $item['project_id'] . '&amp;entry_id=' . (int) $participant['entry_id']); ?>"><?php echo htmlspecialchars((string) $participant['name'], ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><span><?php echo htmlspecialchars((string) $participant['name'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></span>
						<?php if ($item['played'] && $config['show_result'] && $participant['score'] !== null) : ?><span class="jl-ticker-score badge text-bg-dark"><?php echo htmlspecialchars((string) $participant['score'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
					</div><?php endforeach; ?>
					<?php $remaining = max(0, (int) $item['participant_count'] - count($item['participants'])); if ($remaining > 0) : ?><span class="jl-ticker-more small text-body-secondary"><?php echo Text::sprintf('MOD_JOOMLEAGUE_PROGRAMME_TICKER_MORE_PARTICIPANTS', $remaining); ?></span><?php endif; ?>
					</div><?php endif; ?>
					<?php if ($item['played'] && $config['show_result'] && $config['show_decider'] && $item['decider'] !== null) : $values = []; foreach ($item['participants'] as $participant) $values[] = $item['decider']['values'][(int) $participant['participant_id']] ?? '-'; ?><div class="jl-ticker-decider small text-body-secondary"><?php echo htmlspecialchars(implode(':', $values), ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
					<?php if ($config['show_venue'] && $item['venue_name']) : ?><div class="jl-ticker-venue small"><span class="icon-location" aria-hidden="true"></span> <?php if ($config['show_venue_link'] && $item['venue_id']) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&amp;view=venue&amp;venue_id=' . (int) $item['venue_id']); ?>"><?php echo htmlspecialchars((string) $item['venue_name'], ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><?php echo htmlspecialchars((string) $item['venue_name'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></div><?php endif; ?>
					<?php if ($item['show_detail']) : ?><a class="jl-ticker-detail btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomleague&amp;view=eventreport&amp;event_id=' . (int) $item['id']); ?>"><?php echo Text::_('MOD_JOOMLEAGUE_PROGRAMME_TICKER_DETAIL'); ?></a><?php endif; ?>
				</article>
			<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
