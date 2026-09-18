<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$moduleclassSfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$formatter = new IntlDateFormatter(str_replace('-', '_', Factory::getLanguage()->getTag()), IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);
$participantLimit = max(1, min(20, (int) $params->get('participant_limit', 4)));
$orientation = (string) $params->get('orientation', 'vertical');
$orientation = in_array($orientation, ['vertical', 'horizontal'], true) ? $orientation : 'vertical';
$containerClass = $orientation === 'horizontal' ? 'row row-cols-1 row-cols-md-2 g-3' : 'list-group list-group-flush';

$renderResult = static function (array $item, string $orientation) use ($formatter, $params, $participantLimit): void {
	$showDetail = (bool) $item['show_detail'];
	$tag = $showDetail ? 'a' : 'div';
	$shown = array_slice($item['participants'], 0, $participantLimit);
	$remaining = max(0, count($item['participants']) - count($shown));
	$url = Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $item['id']);
	$surfaceClass = $orientation === 'horizontal' ? 'card card-body h-100' : 'list-group-item px-0';
	$surfaceClass .= $showDetail ? ($orientation === 'horizontal' ? ' text-decoration-none' : ' list-group-item-action') : '';
	?>
	<div<?php echo $orientation === 'horizontal' ? ' class="col"' : ''; ?>>
		<<?php echo $tag; ?> class="<?php echo $surfaceClass; ?>"<?php echo $showDetail ? ' href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
			<div class="d-flex justify-content-between align-items-start gap-3 mb-2">
				<div>
					<?php if ((int) $params->get('show_project', 1) === 1) : ?><div class="small fw-semibold text-body-secondary"><?php echo htmlspecialchars((string) $item['project_name'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
					<?php if ((int) $params->get('show_round', 1) === 1 && $item['round_name'] !== '') : ?><div class="small text-body-secondary"><?php echo htmlspecialchars((string) $item['round_name'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
				</div>
				<?php if ((int) $params->get('show_date', 1) === 1) : ?><time class="small text-body-secondary text-nowrap" datetime="<?php echo gmdate('Y-m-d', (int) $item['timestamp']); ?>"><span class="icon-calendar" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $formatter->format((int) $item['timestamp']), ENT_QUOTES, 'UTF-8'); ?></time><?php endif; ?>
			</div>
			<div class="vstack gap-1">
				<?php foreach ($shown as $participant) : ?>
					<div class="d-flex align-items-center justify-content-between gap-2">
						<span class="fw-semibold text-truncate"><?php echo htmlspecialchars((string) $participant['name'], ENT_QUOTES, 'UTF-8'); ?></span>
						<?php if ($participant['score'] !== null) : ?><span class="badge text-bg-dark fs-6"><?php echo htmlspecialchars((string) $participant['score'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<?php if ($remaining > 0) : ?><div class="small text-body-secondary mt-2"><?php echo Text::sprintf('MOD_JOOMLEAGUE_LATEST_RESULTS_MORE', $remaining); ?></div><?php endif; ?>
			<?php if ($item['decider'] !== null) :
				$deciderValues = [];
				foreach ($shown as $participant) {
					$deciderValues[] = $item['decider']['values'][(int) $participant['participant_id']] ?? '-';
				}
				$segmentKey = 'COM_JOOMLEAGUE_SCORE_SEGMENT_' . strtoupper((string) $item['decider']['level_code']);
			?>
				<div class="small text-body-secondary border-top mt-2 pt-2"><?php echo htmlspecialchars(Text::_($segmentKey) . ': ' . implode(':', $deciderValues), ENT_QUOTES, 'UTF-8'); ?></div>
			<?php endif; ?>
		</<?php echo $tag; ?>>
	</div>
	<?php
};
?>
<div class="mod-joomleague-latest-results<?php echo $moduleclassSfx; ?>">
	<?php if (isset($latestResults['error'])) : ?>
		<div class="alert alert-info mb-0"><?php echo Text::_($latestResults['error']); ?></div>
	<?php else : ?>
		<div class="<?php echo $containerClass; ?>">
			<?php foreach ($latestResults['items'] as $item) { $renderResult($item, $orientation); } ?>
		</div>
	<?php endif; ?>
</div>
