<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var Joomleague\Component\Joomleague\Administrator\View\Match\HtmlView $this */

$this->getDocument()->getWebAssetManager()
	->useScript('keepalive')
	->useScript('form.validate')
	->useScript('showon')
	->useStyle('webcomponent.joomla-tab')
	->useScript('webcomponent.joomla-tab'); // potřebné pro matici oprávnění (rules)

$item = $this->item;
$periods = $this->getModel()->getPeriodsByRound((int) ($item->round_id ?? 0));

if ($periods < 1) {
	$periods = 2;
}

$homeSplit = ($item->team1_result_split ?? '') !== '' ? explode(';', (string) $item->team1_result_split) : [];
$awaySplit = ($item->team2_result_split ?? '') !== '' ? explode(';', (string) $item->team2_result_split) : [];

$homeInfo = $this->getModel()->teamInfo((int) ($item->projectteam1_id ?? 0));
$awayInfo = $this->getModel()->teamInfo((int) ($item->projectteam2_id ?? 0));
$logoUrl = static fn (string $logo): string => $logo === '' ? '' : Uri::root(true) . '/' . ltrim($logo, '/');

$splitSummary = [];
$splitN = max(count($homeSplit), count($awaySplit));

for ($s = 0; $s < $splitN; $s++) {
	$splitSummary[] = ($homeSplit[$s] ?? '-') . ':' . ($awaySplit[$s] ?? '-');
}

?>
<style>
	#match-form .jl-match { max-width: 920px; margin: 0 auto; }
	#match-form .jl-team-name { font-size: 1.15rem; font-weight: 700; }
	#match-form .jl-logo { width: 72px; height: 72px; object-fit: contain; margin: 0 auto .5rem; display: block; }
	#match-form .jl-logo-empty { width: 72px; height: 72px; border-radius: 50%; background: rgba(127, 127, 127, .18); display: flex; align-items: center; justify-content: center; margin: 0 auto .5rem; font-size: 1.6rem; font-weight: 700; opacity: .7; }
	#match-form .jl-score input { width: 4.2rem; text-align: center; font-size: 2rem; font-weight: 800; padding: .2rem; }
	#match-form .jl-split-input { max-width: 5.5rem; text-align: center; }
	#match-form details.jl-collapse > summary { cursor: pointer; list-style: none; font-weight: 600; }
	#match-form details.jl-collapse > summary::-webkit-details-marker { display: none; }
	#match-form details.jl-collapse > summary::before { content: "\25B8"; display: inline-block; margin-right: .4rem; transition: transform .15s; }
	#match-form details.jl-collapse[open] > summary::before { transform: rotate(90deg); }
</style>
<form action="<?php echo Route::_('index.php?option=com_joomleague&layout=edit&id=' . (int) $item->id); ?>" method="post" name="adminForm" id="match-form" class="form-validate">
	<div class="jl-match">

		<div class="card mb-3 jl-scoreboard">
			<div class="card-body">
				<div class="row align-items-center text-center g-2">
					<div class="col">
						<?php if ($homeInfo->logo !== '') : ?>
							<img class="jl-logo" src="<?php echo $this->escape($logoUrl($homeInfo->logo)); ?>" alt="">
						<?php else : ?>
							<div class="jl-logo-empty"><?php echo $this->escape(mb_substr($homeInfo->name !== '' ? $homeInfo->name : '?', 0, 1)); ?></div>
						<?php endif; ?>
						<div class="jl-team-name"><?php echo $this->escape($homeInfo->name !== '' ? $homeInfo->name : '—'); ?></div>
						<div class="small text-muted text-uppercase"><?php echo Text::_('COM_JOOMLEAGUE_MATCH_FIELD_HOME'); ?></div>
					</div>
					<div class="col-auto">
						<div class="jl-score d-flex align-items-center gap-2">
							<?php echo $this->form->getInput('team1_result'); ?>
							<span class="fs-2 fw-bold">:</span>
							<?php echo $this->form->getInput('team2_result'); ?>
						</div>
					</div>
					<div class="col">
						<?php if ($awayInfo->logo !== '') : ?>
							<img class="jl-logo" src="<?php echo $this->escape($logoUrl($awayInfo->logo)); ?>" alt="">
						<?php else : ?>
							<div class="jl-logo-empty"><?php echo $this->escape(mb_substr($awayInfo->name !== '' ? $awayInfo->name : '?', 0, 1)); ?></div>
						<?php endif; ?>
						<div class="jl-team-name"><?php echo $this->escape($awayInfo->name !== '' ? $awayInfo->name : '—'); ?></div>
						<div class="small text-muted text-uppercase"><?php echo Text::_('COM_JOOMLEAGUE_MATCH_FIELD_AWAY'); ?></div>
					</div>
				</div>
				<?php if ($splitSummary !== []) : ?>
					<div class="text-center text-muted small mt-2"><?php echo Text::_('COM_JOOMLEAGUE_MATCH_SECTION_SPLIT'); ?>: <?php echo $this->escape(implode(' · ', $splitSummary)); ?></div>
				<?php endif; ?>
			</div>
		</div>

		<div class="card mb-3">
			<div class="card-header"><span class="icon-calendar" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_MATCH_SECTION_MATCH'); ?></div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-6"><?php echo $this->form->renderField('match_date'); ?></div>
					<div class="col-md-6">
						<?php echo $this->form->renderField('playground_id'); ?>
						<div class="form-text"><span class="icon-info-circle" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_MATCH_PLAYGROUND_AUTO_HINT'); ?></div>
					</div>
					<div class="col-md-6"><?php echo $this->form->renderField('crowd'); ?></div>
					<div class="col-md-6"><?php echo $this->form->renderField('match_number'); ?></div>
					<div class="col-md-6"><?php echo $this->form->renderField('match_result_type'); ?></div>
				</div>
			</div>
		</div>

		<div class="card mb-3">
			<div class="card-header"><span class="icon-list" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_MATCH_SECTION_SPLIT'); ?></div>
			<div class="card-body">
				<div data-jl-split-rows>
					<?php $rowCount = max(count($homeSplit), count($awaySplit), $periods, 1); ?>
					<?php for ($i = 0; $i < $rowCount; $i++) : ?>
						<div class="d-flex align-items-center gap-2 mb-2" data-jl-split-row>
							<span class="text-muted" style="min-width:5rem" data-jl-split-label><?php echo Text::sprintf('COM_JOOMLEAGUE_MATCH_PERIOD_N', $i + 1); ?></span>
							<input type="number" step="0.1" class="form-control jl-split-input" name="jform[split_home][]" value="<?php echo $this->escape($homeSplit[$i] ?? ''); ?>">
							<span class="fw-bold">:</span>
							<input type="number" step="0.1" class="form-control jl-split-input" name="jform[split_away][]" value="<?php echo $this->escape($awaySplit[$i] ?? ''); ?>">
							<button type="button" class="btn btn-sm btn-outline-danger" data-jl-split-remove title="<?php echo $this->escape(Text::_('COM_JOOMLEAGUE_MATCH_PART_REMOVE')); ?>"><span class="icon-minus" aria-hidden="true"></span></button>
						</div>
					<?php endfor; ?>
				</div>
				<button type="button" class="btn btn-sm btn-outline-success mt-1" data-jl-split-add><span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_MATCH_PART_ADD'); ?></button>

				<div class="row g-2 mt-2">
					<div class="col-md-6"><?php echo $this->form->renderField('team1_result_ot'); ?></div>
					<div class="col-md-6"><?php echo $this->form->renderField('team2_result_ot'); ?></div>
					<div class="col-md-6"><?php echo $this->form->renderField('team1_result_so'); ?></div>
					<div class="col-md-6"><?php echo $this->form->renderField('team2_result_so'); ?></div>
				</div>
				<?php echo $this->form->renderField('match_result_detail'); ?>
			</div>
		</div>

		<div class="card mb-3">
			<div class="card-header"><span class="icon-pencil-2" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_MATCH_SECTION_REPORT'); ?></div>
			<div class="card-body">
				<?php echo $this->form->renderField('preview'); ?>
				<?php echo $this->form->renderField('summary'); ?>
			</div>
		</div>

		<details class="card mb-3 jl-collapse">
			<summary class="card-header"><?php echo Text::_('COM_JOOMLEAGUE_MATCH_SECTION_STATUS'); ?></summary>
			<div class="card-body">
				<?php echo $this->form->renderField('published'); ?>
				<?php echo $this->form->renderField('count_result'); ?>
				<?php echo $this->form->renderField('show_report'); ?>
				<?php echo $this->form->renderField('cancel'); ?>
				<?php echo $this->form->renderField('cancel_reason'); ?>
				<?php echo $this->form->renderField('id'); ?>
			</div>
		</details>

		<details class="card mb-3 jl-collapse">
			<summary class="card-header"><?php echo Text::_('COM_JOOMLEAGUE_MATCH_CHANGE_TEAMS'); ?></summary>
			<div class="card-body">
				<?php echo $this->form->renderField('projectteam1_id'); ?>
				<?php echo $this->form->renderField('projectteam2_id'); ?>
			</div>
		</details>

		<?php if ($this->form->getFieldset('permissions')) : ?>
			<details class="card mb-3 jl-collapse">
				<summary class="card-header"><?php echo Text::_('JCONFIG_PERMISSIONS_LABEL'); ?></summary>
				<div class="card-body"><?php echo $this->form->renderFieldset('permissions'); ?></div>
			</details>
		<?php endif; ?>

	</div>

	<?php echo $this->form->getInput('round_id'); ?>
	<?php echo $this->form->renderControlFields(); ?>
</form>
<script>
	(function () {
		var partWord = <?php echo json_encode(Text::_('COM_JOOMLEAGUE_MATCH_PART_WORD')); ?>;
		function rows() { return document.querySelectorAll('[data-jl-split-rows] [data-jl-split-row]'); }
		function renumber() {
			rows().forEach(function (row, index) {
				var label = row.querySelector('[data-jl-split-label]');
				if (label) { label.textContent = (index + 1) + '. ' + partWord; }
			});
		}
		document.addEventListener('click', function (event) {
			var addBtn = event.target.closest('[data-jl-split-add]');
			if (addBtn) {
				event.preventDefault();
				var list = rows();
				var clone = list[list.length - 1].cloneNode(true);
				clone.querySelectorAll('input').forEach(function (input) { input.value = ''; });
				list[list.length - 1].parentNode.appendChild(clone);
				renumber();
				return;
			}
			var removeBtn = event.target.closest('[data-jl-split-remove]');
			if (removeBtn) {
				event.preventDefault();
				var list = rows();
				if (list.length <= 1) {
					list[0].querySelectorAll('input').forEach(function (input) { input.value = ''; });
					return;
				}
				removeBtn.closest('[data-jl-split-row]').remove();
				renumber();
			}
		});

		// Provázanost: při změně domácího týmu přepočítej a předvyplň stadion
		document.addEventListener('change', function (event) {
			var homeSel = event.target.closest('[name="jform[projectteam1_id]"]');
			if (!homeSel) { return; }
			var pg = document.querySelector('[name="jform[playground_id]"]');
			if (!pg) { return; }
			fetch('index.php?option=com_joomleague&task=ajax.playground&pt=' + encodeURIComponent(homeSel.value), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then(function (response) { return response.json(); })
				.then(function (res) {
					if (res && typeof res.id !== 'undefined') {
						pg.value = String(res.id);
						pg.dispatchEvent(new Event('change', { bubbles: true }));
					}
				});
		});
	})();
</script>
