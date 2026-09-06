<?php

declare(strict_types=1);

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Venue\HtmlView $this */
$data = $this->venue;
?>
<div class="com-joomleague-venue">
	<?php if (isset($data['error'])) : ?><div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div><?php else : ?>
		<?php $venue = $data['venue']; $location = array_filter([(string) $venue->address, trim((string) $venue->postal_code . ' ' . (string) $venue->city), (string) $venue->region, (string) $venue->country_code]); ?>
		<header class="border-bottom pb-3 mb-4">
			<?php if ($venue->picture) : ?><img src="<?php echo htmlspecialchars((string) $venue->picture, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="img-fluid mb-3" loading="lazy"><?php endif; ?>
			<h1 class="mb-1"><?php echo htmlspecialchars((string) $venue->name, ENT_QUOTES, 'UTF-8'); ?></h1>
			<?php if ($venue->short_name || $venue->nickname) : ?><div class="text-body-secondary"><?php echo htmlspecialchars((string) ($venue->short_name ?: $venue->nickname), ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
			<?php if ($location !== []) : ?><address class="mt-3 mb-0"><?php echo implode('<br>', array_map(static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $location)); ?></address><?php endif; ?>
			<div class="d-flex flex-wrap gap-2 mt-3"><?php if ($venue->club_name) : ?><a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=club&club_id=' . (int) $venue->club_id); ?>"><span class="icon-shield" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $venue->club_name, ENT_QUOTES, 'UTF-8'); ?></a><?php endif; ?><?php if ($venue->website) : ?><a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars((string) $venue->website, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><span class="icon-out-2" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_VENUE_WEBSITE'); ?></a><?php endif; ?></div>
		</header>
		<?php echo LayoutHelper::render('joomleague.fields', ['context' => 'com_joomleague.venue', 'item' => $venue], JPATH_ROOT . '/components/com_joomleague/layouts'); ?>
		<div class="row g-3 mb-4">
			<?php if ($venue->capacity !== null) : ?><div class="col-12 col-md-4"><div class="border rounded p-3 h-100"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_VENUE_CAPACITY_LABEL'); ?></div><strong><?php echo number_format((int) $venue->capacity, 0, '', ' '); ?></strong></div></div><?php endif; ?>
			<?php if ($venue->timezone) : ?><div class="col-12 col-md-4"><div class="border rounded p-3 h-100"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_VENUE_TIMEZONE'); ?></div><strong><?php echo htmlspecialchars((string) $venue->timezone, ENT_QUOTES, 'UTF-8'); ?></strong></div></div><?php endif; ?>
			<?php if ($venue->latitude !== null && $venue->longitude !== null) : ?><div class="col-12 col-md-4"><div class="border rounded p-3 h-100"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_VENUE_COORDINATES'); ?></div><strong><?php echo htmlspecialchars((string) $venue->latitude . ', ' . (string) $venue->longitude, ENT_QUOTES, 'UTF-8'); ?></strong></div></div><?php endif; ?>
		</div>
		<?php if (trim((string) $venue->description) !== '') : ?><div><?php echo nl2br(htmlspecialchars((string) $venue->description, ENT_QUOTES, 'UTF-8')); ?></div><?php endif; ?>
	<?php endif; ?>
</div>
