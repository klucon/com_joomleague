<?php

declare(strict_types=1);

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$data = $this->statistics;
$format = static function (mixed $value): string {
	$formatted = rtrim(rtrim(number_format((float) $value, 9, '.', ''), '0'), '.');
	return $formatted === '' || $formatted === '-0' ? '0' : $formatted;
};
?>
<div class="com-joomleague-participantstats">
	<?php if (isset($data['error'])) : ?><div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
	<?php else : $entry = $data['entry']; ?>
		<header class="border-bottom pb-3 mb-4"><h1 class="mb-1"><?php echo Text::sprintf('COM_JOOMLEAGUE_PARTICIPANTSTATS_HEADING', htmlspecialchars((string) $entry->display_name, ENT_QUOTES, 'UTF-8')); ?></h1><div class="text-body-secondary"><?php echo htmlspecialchars((string) $entry->project_name, ENT_QUOTES, 'UTF-8'); ?></div></header>
		<?php if ($data['rows'] === []) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANTSTATS_EMPTY'); ?></div>
		<?php else : ?><div class="table-responsive"><table class="table table-striped align-middle">
			<thead><tr><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANTSTATS_STATISTIC'); ?></th><th scope="col" class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANTSTATS_APPEARANCES'); ?></th><th scope="col" class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANTSTATS_TOTAL'); ?></th><th scope="col" class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANTSTATS_AVERAGE'); ?></th><th scope="col" class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANTSTATS_MINIMUM'); ?></th><th scope="col" class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANTSTATS_MAXIMUM'); ?></th></tr></thead>
			<tbody><?php foreach ($data['rows'] as $row) : ?><tr><th scope="row"><?php echo htmlspecialchars(Text::_((string) $row->name_key), ENT_QUOTES, 'UTF-8'); ?></th><td class="text-center"><?php echo (int) $row->appearances; ?></td><td class="text-end fw-semibold"><?php echo htmlspecialchars($format($row->total_value), ENT_QUOTES, 'UTF-8'); ?></td><td class="text-end"><?php echo htmlspecialchars($format($row->average_value), ENT_QUOTES, 'UTF-8'); ?></td><td class="text-end"><?php echo htmlspecialchars($format($row->minimum_value), ENT_QUOTES, 'UTF-8'); ?></td><td class="text-end"><?php echo htmlspecialchars($format($row->maximum_value), ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?></tbody>
		</table></div><?php endif; ?>
		<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=participant&project_id=' . (int) $entry->project_id . '&entry_id=' . (int) $entry->id); ?>"><span class="icon-arrow-left" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANTSTATS_BACK'); ?></a>
	<?php endif; ?>
</div>
