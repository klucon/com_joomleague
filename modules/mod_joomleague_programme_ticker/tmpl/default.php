<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$moduleclassSfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$formatter = new IntlDateFormatter(str_replace('-', '_', Factory::getLanguage()->getTag()), IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
$renderGroup = static function (array $items, string $label, string $badgeClass) use ($formatter, $params): void {
	if ($items === []) return;
	?>
	<div class="d-flex flex-wrap align-items-stretch gap-2">
		<span class="badge <?php echo $badgeClass; ?> align-self-center"><?php echo Text::_($label); ?></span>
		<?php foreach ($items as $item) : ?>
			<?php $tag = $item['show_detail'] ? 'a' : 'span'; ?>
			<<?php echo $tag; ?> class="border rounded px-2 py-1 text-body text-decoration-none"<?php echo $item['show_detail'] ? ' href="' . htmlspecialchars(Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $item['id']), ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
				<?php if ((int) $params->get('show_project', 0) === 1) : ?><small class="text-body-secondary me-1"><?php echo htmlspecialchars((string) $item['project_name'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
				<?php foreach ($item['participants'] as $index => $participant) : ?>
					<?php if ($index > 0) : ?><span class="text-body-secondary mx-1">·</span><?php endif; ?>
					<strong><?php echo htmlspecialchars((string) $participant['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
					<?php if ((int) $params->get('show_result', 1) === 1 && $participant['score'] !== null) : ?><span class="badge text-bg-secondary"><?php echo htmlspecialchars((string) $participant['score'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
				<?php endforeach; ?>
				<?php if ((int) $params->get('show_date', 1) === 1) : ?><small class="text-body-secondary ms-1"><?php echo htmlspecialchars((string) $formatter->format((int) $item['timestamp']), ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
			</<?php echo $tag; ?>>
		<?php endforeach; ?>
	</div>
	<?php
};
?>
<div class="mod-joomleague-programme-ticker<?php echo $moduleclassSfx; ?>">
	<?php if (isset($ticker['error'])) : ?>
		<div class="alert alert-info mb-0"><?php echo Text::_($ticker['error']); ?></div>
	<?php else : ?>
		<?php $renderGroup($ticker['completed'] ?? [], 'MOD_JOOMLEAGUE_PROGRAMME_TICKER_COMPLETED', 'text-bg-success'); ?>
		<?php if (($ticker['completed'] ?? []) !== [] && ($ticker['upcoming'] ?? []) !== []) : ?><hr class="my-2"><?php endif; ?>
		<?php $renderGroup($ticker['upcoming'] ?? [], 'MOD_JOOMLEAGUE_PROGRAMME_TICKER_UPCOMING', 'text-bg-primary'); ?>
	<?php endif; ?>
</div>
