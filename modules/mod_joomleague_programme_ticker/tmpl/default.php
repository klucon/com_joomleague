<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$moduleclassSfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$orientation = (string) $params->get('orientation', 'horizontal');
$orientation = in_array($orientation, ['horizontal', 'vertical'], true) ? $orientation : 'horizontal';
$formatter = new IntlDateFormatter(
	str_replace('-', '_', Factory::getLanguage()->getTag()),
	IntlDateFormatter::MEDIUM,
	IntlDateFormatter::SHORT
);

$renderItem = static function (array $item, string $class) use ($formatter, $params): void {
	$isLinked = (bool) ($item['show_detail'] ?? false);
	$tag = $isLinked ? 'a' : 'div';
	$url = $isLinked
		? Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $item['id'])
		: '';
	?>
	<<?php echo $tag; ?> class="<?php echo $class; ?> text-body text-decoration-none"<?php echo $isLinked ? ' href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
		<?php if ((int) $params->get('show_project', 0) === 1) : ?>
			<div class="small fw-semibold text-body-secondary mb-2"><?php echo htmlspecialchars((string) $item['project_name'], ENT_QUOTES, 'UTF-8'); ?></div>
		<?php endif; ?>

		<div class="d-flex flex-column gap-1">
			<?php foreach ($item['participants'] as $participant) : ?>
				<div class="d-flex align-items-center justify-content-between gap-3">
					<span class="fw-semibold text-break"><?php echo htmlspecialchars((string) $participant['name'], ENT_QUOTES, 'UTF-8'); ?></span>
					<?php if ((int) $params->get('show_result', 1) === 1 && $participant['score'] !== null) : ?>
						<span class="badge text-bg-secondary flex-shrink-0"><?php echo htmlspecialchars((string) $participant['score'], ENT_QUOTES, 'UTF-8'); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ((int) $params->get('show_date', 1) === 1) : ?>
			<div class="small text-body-secondary mt-2">
				<span class="icon-calendar me-1" aria-hidden="true"></span>
				<?php echo htmlspecialchars((string) $formatter->format((int) $item['timestamp']), ENT_QUOTES, 'UTF-8'); ?>
			</div>
		<?php endif; ?>
	</<?php echo $tag; ?>>
	<?php
};

$renderGroup = static function (array $items, string $label, string $icon, string $badgeClass) use ($orientation, $renderItem): void {
	if ($items === []) {
		return;
	}
	?>
	<section class="mb-4" aria-label="<?php echo htmlspecialchars(Text::_($label), ENT_QUOTES, 'UTF-8'); ?>">
		<h3 class="h6 d-flex align-items-center gap-2 border-bottom pb-2 mb-3">
			<span class="icon-<?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></span>
			<span><?php echo Text::_($label); ?></span>
			<span class="badge <?php echo $badgeClass; ?> ms-auto"><?php echo count($items); ?></span>
		</h3>

		<?php if ($orientation === 'horizontal') : ?>
			<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
				<?php foreach ($items as $item) : ?>
					<div class="col">
						<?php $renderItem($item, 'card card-body h-100'); ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="list-group list-group-flush">
				<?php foreach ($items as $item) : ?>
					<?php $renderItem($item, 'list-group-item list-group-item-action px-0 py-3 bg-transparent'); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
	<?php
};

$completed = $ticker['completed'] ?? [];
$upcoming = $ticker['upcoming'] ?? [];
?>
<div class="mod-joomleague-programme-ticker<?php echo $moduleclassSfx; ?>">
	<?php if (isset($ticker['error'])) : ?>
		<div class="alert alert-info mb-0"><?php echo Text::_($ticker['error']); ?></div>
	<?php elseif ($completed === [] && $upcoming === []) : ?>
		<div class="alert alert-info mb-0"><?php echo Text::_('MOD_JOOMLEAGUE_PROGRAMME_TICKER_EMPTY'); ?></div>
	<?php else : ?>
		<?php $renderGroup($completed, 'MOD_JOOMLEAGUE_PROGRAMME_TICKER_COMPLETED', 'check-circle', 'text-bg-success'); ?>
		<?php $renderGroup($upcoming, 'MOD_JOOMLEAGUE_PROGRAMME_TICKER_UPCOMING', 'calendar', 'text-bg-primary'); ?>
	<?php endif; ?>
</div>
