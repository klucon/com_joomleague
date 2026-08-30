<?php

declare(strict_types=1);

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;
$data = $this->ranking;
$formatValue = static function (mixed $value): string {
	$formatted = rtrim(rtrim((string) $value, '0'), '.');
	return $formatted === '' || $formatted === '-' ? '0' : $formatted;
};
?>
<div class="com-joomleague-statranking">
	<?php if (isset($data['error'])) : ?><div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
	<?php else : ?>
		<h1><?php echo Text::sprintf('COM_JOOMLEAGUE_STATRANKING_PAGE_TITLE', htmlspecialchars((string) $data['project']->name, ENT_QUOTES, 'UTF-8')); ?></h1>
		<?php if ($data['definitions'] === []) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_STATRANKING_EMPTY'); ?></div>
		<?php else : ?>
			<nav class="nav nav-pills gap-2 mb-4" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_STATRANKING_STATISTIC_LABEL'); ?>">
			<?php foreach ($data['definitions'] as $definition) : $active = (string) $definition->statistic_code === (string) $data['selected_code']; ?>
				<a class="nav-link<?php echo $active ? ' active' : ''; ?>"<?php echo $active ? ' aria-current="page"' : ''; ?> href="<?php echo Route::_('index.php?option=com_joomleague&view=statranking&project_id=' . (int) $data['project']->id . '&statistic_code=' . rawurlencode((string) $definition->statistic_code)); ?>"><?php echo Text::_((string) $definition->name_key); ?></a>
			<?php endforeach; ?>
			</nav>
			<?php if ($data['rows'] === []) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_STATRANKING_NO_VALUES'); ?></div>
			<?php else : ?><div class="table-responsive"><table class="table table-striped align-middle">
				<thead><tr><th scope="col" class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_STATRANKING_RANK'); ?></th><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_STATRANKING_PARTICIPANT'); ?></th><th scope="col" class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_STATRANKING_APPEARANCES'); ?></th><th scope="col" class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_STATRANKING_VALUE'); ?></th></tr></thead>
				<tbody><?php foreach ($data['rows'] as $row) : $targetUrl = (string) $row->target_kind === 'person' ? Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $row->target_id) : Route::_('index.php?option=com_joomleague&view=participant&project_id=' . (int) $data['project']->id . '&entry_id=' . (int) $row->target_id); ?><tr><td class="text-center fw-semibold"><?php echo (int) $row->rank; ?></td><th scope="row"><a href="<?php echo $targetUrl; ?>"><?php echo htmlspecialchars((string) $row->display_name, ENT_QUOTES, 'UTF-8'); ?></a></th><td class="text-center"><?php echo (int) $row->appearances; ?></td><td class="text-end fw-semibold"><?php echo htmlspecialchars($formatValue($row->total_value), ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?></tbody>
			</table></div><?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>
</div>
