<?php
declare(strict_types=1);
use Joomla\CMS\Language\Text;
defined('_JEXEC') or die;
$monthDate = new DateTimeImmutable($calendar['month'], new DateTimeZone($timezone));
$monthFormatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, $timezone, null, 'LLLL yyyy');
$weekdayFormatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, $timezone, null, 'EEEEE');
$counts = [];
foreach ($calendar['groups'] ?? [] as $dayGroup) $counts[$dayGroup['date']] = count($dayGroup['items']);
?>
<div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
    <h3 class="h6 mb-0"><?php echo htmlspecialchars((string) $monthFormatter->format($monthDate), ENT_QUOTES, 'UTF-8'); ?></h3>
    <nav class="d-flex gap-1" aria-label="<?php echo Text::_('MOD_JOOMLEAGUE_CALENDAR_MONTH_NAV'); ?>">
        <?php foreach (['previous' => 'icon-angle-left', 'today' => 'icon-calendar', 'next' => 'icon-angle-right'] as $command => $icon) : ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($calendar[$command . '_url'], ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo Text::_('MOD_JOOMLEAGUE_CALENDAR_NAV_' . strtoupper($command)); ?>" aria-label="<?php echo Text::_('MOD_JOOMLEAGUE_CALENDAR_NAV_' . strtoupper($command)); ?>"><span class="<?php echo $icon; ?>" aria-hidden="true"></span></a>
        <?php endforeach; ?>
    </nav>
</div>
<table class="table table-bordered text-center" style="table-layout:fixed;width:100%">
    <thead><tr><?php foreach ($calendar['grid'][0] as $cell) : ?>
        <th scope="col" class="p-1"><abbr title="<?php echo htmlspecialchars((new IntlDateFormatter($locale, -1, -1, $timezone, null, 'EEEE'))->format(new DateTimeImmutable($cell['date'], new DateTimeZone($timezone))), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $weekdayFormatter->format(new DateTimeImmutable($cell['date'], new DateTimeZone($timezone))), ENT_QUOTES, 'UTF-8'); ?></abbr></th>
    <?php endforeach; ?></tr></thead>
    <tbody><?php foreach ($calendar['grid'] as $week) : ?><tr>
        <?php foreach ($week as $cell) : ?><td class="p-1 align-middle<?php echo $cell['current'] ? '' : ' text-body-secondary'; ?>" style="height:3.5rem">
            <?php if (isset($counts[$cell['date']])) : ?>
                <a class="d-block fw-semibold" href="#jl-calendar-<?php echo (int) $calendar['module_id']; ?>-<?php echo $cell['date']; ?>" aria-label="<?php echo htmlspecialchars($cell['date'] . ': ' . Text::plural('MOD_JOOMLEAGUE_CALENDAR_EVENT_COUNT', $counts[$cell['date']]), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $cell['day']; ?><small class="d-block text-body-secondary"><?php echo $counts[$cell['date']]; ?></small></a>
            <?php else : ?><?php echo $cell['day']; ?><?php endif; ?>
        </td><?php endforeach; ?>
    </tr><?php endforeach; ?></tbody>
</table>
