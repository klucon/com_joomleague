<?php

declare(strict_types=1);

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$data = $this->matrix;
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="com-joomleague-resultmatrix">
	<?php if (isset($data['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
	<?php elseif (!$data['supported']) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_RESULTMATRIX_UNSUPPORTED'); ?></div>
	<?php else : ?>
		<header class="mb-4">
			<h1><?php echo Text::sprintf('COM_JOOMLEAGUE_RESULTMATRIX_PAGE_TITLE', $escape($data['project']->name)); ?></h1>
			<?php if ($data['stage']) : ?><p class="h5 text-body-secondary"><?php echo $escape($data['stage']->name); ?></p><?php endif; ?>
			<p class="text-body-secondary mb-0"><?php echo Text::_('COM_JOOMLEAGUE_RESULTMATRIX_INTRO'); ?></p>
		</header>
		<?php if (count($data['stages']) > 1) : ?>
			<nav class="nav nav-pills gap-2 mb-4" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_RESULTMATRIX_STAGE_LABEL'); ?>">
				<a class="nav-link<?php echo $data['stage'] === null ? ' active' : ''; ?>" href="<?php echo Route::_('index.php?option=com_joomleague&view=resultmatrix&project_id=' . (int) $data['project']->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_RESULTMATRIX_ALL_STAGES'); ?></a>
				<?php foreach ($data['stages'] as $stage) : ?><a class="nav-link<?php echo $data['stage'] && (int) $data['stage']->id === (int) $stage->id ? ' active' : ''; ?>" href="<?php echo Route::_('index.php?option=com_joomleague&view=resultmatrix&project_id=' . (int) $data['project']->id . '&stage_id=' . (int) $stage->id); ?>"><?php echo $escape($stage->name); ?></a><?php endforeach; ?>
			</nav>
		<?php endif; ?>
		<?php if ($data['entries'] === [] || $data['cells'] === []) : ?>
			<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_RESULTMATRIX_EMPTY'); ?></div>
		<?php else : ?>
			<div class="table-responsive">
				<table class="table table-bordered table-hover align-middle text-center">
					<caption class="visually-hidden"><?php echo Text::sprintf('COM_JOOMLEAGUE_RESULTMATRIX_PAGE_TITLE', $escape($data['project']->name)); ?></caption>
					<thead><tr><th class="text-start" scope="col"><?php echo Text::_('COM_JOOMLEAGUE_RESULTMATRIX_PARTICIPANT'); ?></th><?php foreach ($data['entries'] as $entry) : ?><th scope="col" title="<?php echo $escape($entry->display_name); ?>"><?php echo $escape($entry->short_name); ?></th><?php endforeach; ?></tr></thead>
					<tbody>
					<?php foreach ($data['entries'] as $row) : ?><tr><th class="text-start" scope="row"><?php echo $escape($row->display_name); ?></th>
						<?php foreach ($data['entries'] as $column) : ?>
							<?php if ((int) $row->id === (int) $column->id) : ?><td class="table-secondary" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_RESULTMATRIX_SAME_PARTICIPANT'); ?>">&mdash;</td>
							<?php else : $results = $data['cells'][(int) $row->id][(int) $column->id] ?? []; ?><td>
								<?php if ($results === []) : ?><span class="text-body-secondary">&ndash;</span><?php else : ?>
					<div class="d-flex flex-column gap-1"><?php foreach ($results as $result) : $first = $result['values'][0]['value'] ?? '?'; $second = $result['values'][1]['value'] ?? '?'; ?><a class="badge text-bg-secondary text-decoration-none" title="<?php echo $escape($result['round_name']); ?>" href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $result['match_id']); ?>"><?php echo $escape($first . ':' . $second); ?></a><?php endforeach; ?></div>
								<?php endif; ?></td><?php endif; ?>
						<?php endforeach; ?>
					</tr><?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
