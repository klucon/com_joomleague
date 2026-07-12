<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView $this */

$this->getDocument()->getWebAssetManager()->useScript('multiselect');
$user = $this->getCurrentUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirection = $this->escape($this->state->get('list.direction'));
$dashboardStyle = Uri::root(true) . '/media/com_joomleague/css/dashboard.css?v=0.13.6';
?>
<?php if ((property_exists($this, 'projectContext') && $this->projectContext) || (property_exists($this, 'roundContext') && $this->roundContext)) : ?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($dashboardStyle, ENT_QUOTES, 'UTF-8'); ?>">
<?php endif; ?>
<?php if (property_exists($this, 'projectContext') && $this->projectContext) : $project = $this->projectContext; ?>
<div class="com-joomleague-dashboard com-joomleague-workflow mb-4">
	<div class="jl-section-panel">
		<span class="jl-section-panel__icon icon-calendar" aria-hidden="true"></span>
		<div class="jl-section-panel__content">
			<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_CONTEXT'); ?></p>
			<h2 class="h4 mb-1"><?php echo $this->escape($project->name); ?></h2>
			<p class="mb-2"><?php echo $this->escape(trim(($project->league ?? '') . ' · ' . ($project->season ?? '') . ' · ' . ($project->sport ?? ''), " \t\n\r " . chr(11) . '·')); ?></p>
			<a class="jl-section-back" href="<?php echo Route::_('index.php?option=com_joomleague&view=projectpanel&project_id=' . (int) $project->id); ?>">
				<span class="icon-arrow-left" aria-hidden="true"></span>
				<?php echo Text::_('COM_JOOMLEAGUE_BACK_TO_PROJECT_PANEL'); ?>
			</a>
		</div>
		<div class="jl-section-panel__stats">
			<span><strong><?php echo number_format((int) $project->round_count, 0, ',', ' '); ?></strong><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_ROUNDS'); ?></span>
			<span><strong><?php echo number_format((int) $project->match_count, 0, ',', ' '); ?></strong><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_MATCHES'); ?></span>
		</div>
	</div>
</div>
<?php endif; ?>
<?php if (property_exists($this, 'roundContext') && $this->roundContext) : $round=$this->roundContext; ?>
<div class="com-joomleague-dashboard com-joomleague-workflow mb-4">
	<div class="jl-section-panel">
		<span class="jl-section-panel__icon icon-list" aria-hidden="true"></span>
		<div class="jl-section-panel__content">
			<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_MATCH_ROUND_CONTEXT'); ?></p>
			<h2 class="h4 mb-1"><?php echo Text::sprintf('COM_JOOMLEAGUE_MATCH_CONTEXT', $this->escape($round->name), $this->escape($round->project_name)); ?></h2>
			<p class="mb-2"><?php echo Text::sprintf('COM_JOOMLEAGUE_MATCH_TIMEZONE', $this->escape($round->timezone)); ?></p>
			<a class="jl-section-back" href="<?php echo Route::_('index.php?option=com_joomleague&view=projectpanel&project_id=' . (int) $round->project_id); ?>">
				<span class="icon-arrow-left" aria-hidden="true"></span>
				<?php echo Text::_('COM_JOOMLEAGUE_BACK_TO_PROJECT_PANEL'); ?>
			</a>
		</div>
		<div class="jl-workflow-round-nav">
			<?php if ($round->previous_id) : ?>
				<a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_joomleague&view=matches&round_id=' . (int) $round->previous_id); ?>"><span class="icon-chevron-left" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_PREVIOUS_ROUND'); ?></a>
			<?php endif; ?>
			<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=rounds&project_id=' . (int) $round->project_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_ROUNDS'); ?></a>
			<?php if ($round->next_id) : ?>
				<a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_joomleague&view=matches&round_id=' . (int) $round->next_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_NEXT_ROUND'); ?> <span class="icon-chevron-right" aria-hidden="true"></span></a>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php endif; ?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=' . $this->entity['plural'] . ($this->entity['list_action_append'] ?? '')); ?>" method="post" name="adminForm" id="adminForm"><div id="j-main-container" class="j-main-container">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<?php if ($this->items === []) : ?><div class="alert alert-info"><span class="icon-info-circle" aria-hidden="true"></span> <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
	<?php else : ?><table class="table"><caption class="visually-hidden"><?php echo Text::_($this->entity['caption']); ?></caption><thead><tr>
		<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
		<?php if (!empty($this->entity['state'])) : ?><th class="w-1 text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirection, $listOrder); ?></th><?php endif; ?>
		<?php foreach ($this->entity['columns'] as $column) : ?><th class="<?php echo $this->escape($column['class'] ?? ''); ?>"><?php echo !empty($column['sort']) ? HTMLHelper::_('searchtools.sort', $column['label'], $column['sort'], $listDirection, $listOrder) : Text::_($column['label']); ?></th><?php endforeach; ?>
	</tr></thead><tbody>
	<?php foreach ($this->items as $i => $item) :
		$canEdit = ($this->entity['can_edit'] ?? true) && $user->authorise('core.edit', 'com_joomleague');
		$checkedOut = (int) ($item->checked_out ?? 0);
		$canCheckin = $user->authorise('core.manage', 'com_checkin') || $checkedOut === (int) $user->id || $checkedOut === 0;
		$canChange = $user->authorise('core.edit.state', 'com_joomleague') && $canCheckin;
	?><tr><td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', (string) $item->{$this->entity['primary']}); ?></td>
		<?php if (!empty($this->entity['state'])) : ?><td class="text-center"><?php echo HTMLHelper::_('jgrid.published', $item->published, $i, $this->entity['plural'] . '.', $canChange); ?></td><?php endif; ?>
		<?php foreach ($this->entity['columns'] as $index => $column) : $value = $item->{$column['field']} ?? ''; ?><<?php echo $index === 0 ? 'th scope="row"' : 'td'; ?> class="<?php echo $this->escape($column['class'] ?? ''); ?>">
			<?php if (($column['type'] ?? '') === 'image') : $image = HTMLHelper::cleanImageURL((string) $value); $src = $image->url === '' ? '' : Uri::root(true) . '/' . ltrim($image->url, '/'); ?>
				<?php if ($src !== '') : ?><img src="<?php echo $this->escape($src); ?>" alt="" width="32" height="32" loading="lazy" class="rounded object-fit-contain"><?php else : ?><span class="icon-image text-muted" aria-hidden="true"></span><?php endif; ?>
			<?php elseif (($column['type'] ?? '') === 'matchdata') : ?>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=matchdata&match_id=' . (int) $item->id . '&section=' . $column['section']); ?>"><?php echo (int) $value; ?> <span class="icon-edit" aria-hidden="true"></span></a>
			<?php elseif (($column['type'] ?? '') === 'roundmatches') : ?>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=matches&round_id=' . (int) $item->id); ?>"><?php echo (int) $value; ?> <span class="icon-edit" aria-hidden="true"></span></a>
			<?php elseif (($column['type'] ?? '') === 'treetonodes') : ?>
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=treetonodes&treeto_id=' . (int) $item->id); ?>"><?php echo (int) $value; ?> <span class="icon-tree-2" aria-hidden="true"></span></a>
			<?php elseif (($column['type'] ?? '') === 'treetogenerate') : ?>
				<a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&task=treeto.generate&id=' . (int) $item->id . '&' . Session::getFormToken() . '=1'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_TREETO_GENERATE'); ?></a>
			<?php elseif (($column['type'] ?? '') === 'predictionrecalculate') : ?>
				<a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&task=predictiongame.recalculate&id=' . (int) $item->id . '&' . Session::getFormToken() . '=1'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_PREDICTIONGAME_RECALCULATE'); ?></a>
			<?php elseif (($column['type'] ?? '') === 'roundmove') : ?>
				<div class="d-flex gap-1 align-items-center">
					<input type="date" class="form-control form-control-sm" style="width:9.5rem" name="move_date[<?php echo (int) $item->id; ?>]" value="<?php echo $this->escape(substr((string) $value, 0, 10)); ?>">
					<button type="button" class="btn btn-sm btn-secondary" data-jl-round-move="<?php echo (int) $item->id; ?>"><span class="icon-calendar" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_ROUND_MOVE'); ?></button>
				</div>
			<?php elseif (($column['type'] ?? '') === 'matchdatetime') : ?>
				<input type="datetime-local" class="form-control form-control-sm jl-match-date" data-jl-match-date data-id="<?php echo (int) $item->id; ?>" value="<?php echo $this->escape($value === '' || $value === null ? '' : str_replace(' ', 'T', substr((string) $value, 0, 16))); ?>">
			<?php elseif (($column['type'] ?? '') === 'articlesync') : $hasArticle = !empty($item->has_article); ?>
				<a class="btn btn-sm <?php echo $hasArticle ? 'btn-warning' : 'btn-success'; ?>" href="<?php echo Route::_('index.php?option=com_joomleague&task=match.syncarticle&id=' . (int) $item->id . '&' . Session::getFormToken() . '=1'); ?>"><span class="icon-file-2" aria-hidden="true"></span> <?php echo Text::_($hasArticle ? 'COM_JOOMLEAGUE_MATCH_UPDATE_ARTICLE' : 'COM_JOOMLEAGUE_MATCH_CREATE_ARTICLE'); ?></a>
			<?php elseif (($column['type'] ?? '') === 'splitscore') : ?>
				<?php
				$splitHome = ($item->team1_result_split ?? '') !== '' ? explode(';', (string) $item->team1_result_split) : [];
				$splitAway = ($item->team2_result_split ?? '') !== '' ? explode(';', (string) $item->team2_result_split) : [];
				$splitN = max(count($splitHome), count($splitAway));
				$splitParts = [];
				for ($s = 0; $s < $splitN; $s++) {
					$splitParts[] = ($splitHome[$s] ?? '-') . ':' . ($splitAway[$s] ?? '-');
				}
				echo $this->escape(implode(', ', $splitParts));
				?>
			<?php elseif (($column['type'] ?? '') === 'datetime') : ?>
				<?php echo $this->escape($value === '' || $value === null ? '' : substr((string) $value, 0, 16)); ?>
			<?php elseif (($column['type'] ?? '') === 'country') : ?>
				<?php echo \Joomleague\Component\Joomleague\Administrator\Helper\FlagHelper::render((string) $value); ?>
			<?php elseif (($column['type'] ?? '') === 'lang') : ?>
				<?php $translatedValue = Text::_((string) $value); ?>
				<?php if ($index === 0 && $canEdit) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&task=' . $this->entity['singular'] . '.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($translatedValue); ?></a><?php else : echo $this->escape($translatedValue); endif; ?>
			<?php elseif ($index === 0) : ?>
				<?php if ($checkedOut) { echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor ?? '', $item->checked_out_time ?? null, $this->entity['plural'] . '.', $canCheckin); } ?>
				<?php if ($canEdit) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&task=' . $this->entity['singular'] . '.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape((string) $value); ?></a><?php else : echo $this->escape((string) $value); endif; ?>
			<?php else : ?><?php echo $this->escape((string) $value); ?><?php endif; ?>
		</<?php echo $index === 0 ? 'th' : 'td'; ?>><?php endforeach; ?>
	</tr><?php endforeach; ?></tbody></table><?php echo $this->pagination->getListFooter(); ?><?php endif; ?><?php echo $this->filterForm->renderControlFields(); ?>
</div>
<?php if (in_array('roundmove', array_column($this->entity['columns'], 'type'), true)) : ?>
	<input type="hidden" name="move_id" value="">
	<script>
		document.addEventListener('click', function (event) {
			var button = event.target.closest('[data-jl-round-move]');
			if (!button) { return; }
			event.preventDefault();
			var form = document.getElementById('adminForm');
			form.querySelector('input[name="move_id"]').value = button.getAttribute('data-jl-round-move');
			Joomla.submitform('round.move', form);
		});
	</script>
<?php endif; ?>
<?php if (in_array('matchdatetime', array_column($this->entity['columns'], 'type'), true)) : ?>
	<script>
		(function () {
			var previous = {};
			// Zapamatuj hodnotu při vstupu do pole
			document.addEventListener('focusin', function (event) {
				var input = event.target.closest('[data-jl-match-date]');
				if (input) { previous[input.getAttribute('data-id')] = input.value; }
			});
			// Ulož až při opuštění pole (kliknutí jinam) a jen když se hodnota změnila
			document.addEventListener('focusout', function (event) {
				var input = event.target.closest('[data-jl-match-date]');
				if (!input) { return; }
				var id = input.getAttribute('data-id');
				if (previous[id] === input.value) { return; }
				var data = new FormData();
				data.append('id', id);
				data.append('match_date', input.value);
				data.append(Joomla.getOptions('csrf.token'), '1');
				input.classList.remove('is-valid', 'is-invalid');
				fetch('index.php?option=com_joomleague&task=match.savedate', {
					method: 'POST',
					body: data,
					headers: { 'X-Requested-With': 'XMLHttpRequest' }
				})
					.then(function (response) { return response.json(); })
					.then(function (res) {
						input.classList.add(res.success ? 'is-valid' : 'is-invalid');
						if (res.success) {
							if (res.value) { input.value = res.value; }
							previous[id] = input.value;
						} else if (res.message) {
							alert(res.message);
						}
					})
					.catch(function () { input.classList.add('is-invalid'); });
			});
		})();
	</script>
<?php endif; ?>
<?php echo HTMLHelper::_('form.token'); ?></form>
