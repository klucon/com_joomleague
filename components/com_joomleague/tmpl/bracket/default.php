<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Bracket\HtmlView $this */
$bracket = $this->bracket;

$cardWidth = 200;
$cardHeight = (int) ($bracket['card_height'] ?? 52);
$columnGap = 40;
$columnWidth = $cardWidth + $columnGap;
$roundCount = isset($bracket['rounds']) ? count($bracket['rounds']) : 0;

$formatValue = static function (?object $value): string {
	if ($value === null) return '';
	if ($value->numeric_value !== null) return rtrim(rtrim((string) $value->numeric_value, '0'), '.');
	if ($value->text_value !== null && $value->text_value !== '') return (string) $value->text_value;
	if ($value->status_code !== null && $value->status_code !== '') return (string) $value->status_code;
	return $value->result_rank !== null ? '#' . (int) $value->result_rank : '';
};
?>
<div class="com-joomleague-bracket">
	<?php if (isset($bracket['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($bracket['error']); ?></div>
	<?php else : ?>
		<h1 class="com-joomleague-bracket__title"><?php echo Text::_('COM_JOOMLEAGUE_BRACKET_VIEW_TITLE'); ?></h1>
		<p class="h4"><?php echo htmlspecialchars((string) $bracket['project']->name, ENT_QUOTES, 'UTF-8'); ?></p>

		<style>
			.jl-bracket-nav { display: flex; align-items: center; gap: .5rem; margin-bottom: .5rem; }
			.jl-bracket-nav__btn { border: 1px solid var(--bs-border-color, #dee2e6); color: var(--bs-body-color, inherit); background: var(--bs-body-bg, #fff); border-radius: .375rem; width: 2.25rem; height: 2.25rem; font-size: 1.1rem; line-height: 1; cursor: pointer; }
			.jl-bracket-nav__btn:disabled { opacity: .35; cursor: default; }
			.jl-bracket-nav__btn:not(:disabled):hover { background: var(--bs-tertiary-bg, #f1f3f5); }
			.jl-bracket-viewport { overflow: hidden; border: 1px solid var(--bs-border-color, #dee2e6); border-radius: .375rem; }
			.jl-bracket-scroll { overflow-y: auto; overflow-x: auto; max-height: 75vh; scroll-behavior: smooth; }
			/* Horizontal movement is arrow-driven (see #jl-bracket-prev/next below), so the
			   horizontal scrollbar itself is just visual clutter — hidden where CSS allows
			   hiding a single axis (WebKit/Blink); Firefox still shows it, which is a harmless
			   fallback since scrollLeft keeps working either way, just with a visible bar. */
			.jl-bracket-scroll::-webkit-scrollbar:horizontal { display: none; }
			.jl-bracket-canvas { position: relative; }
			.jl-bracket-header { position: sticky; top: 0; z-index: 2; display: flex; background: var(--bs-body-bg, #fff); border-bottom: 1px solid var(--bs-border-color, #dee2e6); }
			.jl-bracket-header__col { flex: 0 0 <?php echo $columnWidth; ?>px; text-align: center; font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; color: var(--bs-secondary-color, #6c757d); padding: .5rem 0; }
			.jl-bracket-svg { position: absolute; top: 0; left: 0; pointer-events: none; }
			.jl-bracket-match { position: absolute; background: var(--bs-body-bg, #fff); border: 1px solid var(--bs-border-color, #dee2e6); border-radius: .375rem; padding: .3rem .55rem; font-size: .78rem; line-height: 1.25; box-sizing: border-box; overflow: hidden; }
			.jl-bracket-match--active { border-color: var(--bs-primary, #0d6efd); box-shadow: inset 0 0 0 1px var(--bs-primary, #0d6efd); }
			.jl-bracket-match__participant { display: flex; justify-content: space-between; gap: .4rem; min-height: 22px; }
			.jl-bracket-match__participant--winner { font-weight: 700; }
			.jl-bracket-match__name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: pointer; border-radius: .2rem; }
			.jl-bracket-match__name:hover { background: var(--bs-tertiary-bg, #f1f3f5); }
			.jl-bracket-match__name--active { color: var(--bs-primary, #0d6efd); text-decoration: underline; }
		</style>

		<div class="jl-bracket-nav">
			<button type="button" class="jl-bracket-nav__btn" id="jl-bracket-prev" aria-label="<?php echo htmlspecialchars(Text::_('COM_JOOMLEAGUE_BRACKET_NAV_PREV'), ENT_QUOTES, 'UTF-8'); ?>">‹</button>
			<strong id="jl-bracket-current"></strong>
			<button type="button" class="jl-bracket-nav__btn" id="jl-bracket-next" aria-label="<?php echo htmlspecialchars(Text::_('COM_JOOMLEAGUE_BRACKET_NAV_NEXT'), ENT_QUOTES, 'UTF-8'); ?>">›</button>
		</div>

		<div class="jl-bracket-viewport">
			<div class="jl-bracket-scroll" id="jl-bracket-scroll">
				<div class="jl-bracket-header">
					<?php foreach ($bracket['rounds'] as $round) : ?>
						<div class="jl-bracket-header__col"><?php echo htmlspecialchars($round['name'], ENT_QUOTES, 'UTF-8'); ?></div>
					<?php endforeach; ?>
				</div>
				<div class="jl-bracket-canvas" style="width:<?php echo $roundCount * $columnWidth; ?>px;height:<?php echo (int) $bracket['canvas_height']; ?>px;">
					<svg class="jl-bracket-svg" width="<?php echo $roundCount * $columnWidth; ?>" height="<?php echo (int) $bracket['canvas_height']; ?>">
						<?php foreach ($bracket['rounds'] as $roundIndex => $round) : ?>
							<?php if ($roundIndex === 0 || !$round['in_target_stage']) : continue; endif; ?>
							<?php foreach ($round['items'] as $item) : ?>
								<?php foreach ($item->feeder_ids as $i => $feederId) : ?>
								<line
									data-to="<?php echo (int) $item->id; ?>" data-from="<?php echo $feederId; ?>"
										x1="<?php echo ($roundIndex - 1) * $columnWidth + $cardWidth; ?>"
									y1="<?php echo $item->feeder_ys[$i] + $cardHeight / 2; ?>"
										x2="<?php echo $roundIndex * $columnWidth; ?>"
									y2="<?php echo $item->y + $cardHeight / 2; ?>"
										stroke="#adb5bd" stroke-width="2"
									/>
								<?php endforeach; ?>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</svg>

					<?php foreach ($bracket['rounds'] as $roundIndex => $round) : ?>
						<?php foreach ($round['items'] as $item) : ?>
							<div class="jl-bracket-match" data-id="<?php echo (int) $item->id; ?>" style="left:<?php echo $roundIndex * $columnWidth; ?>px;top:<?php echo $item->y; ?>px;width:<?php echo $cardWidth; ?>px;height:<?php echo $cardHeight; ?>px;">
								<?php if ($item->participants === []) : ?><div class="text-body-secondary">—</div><?php endif; ?>
								<?php foreach ($item->participants as $participant) : ?>
									<div class="jl-bracket-match__participant<?php echo $participant->winner ? ' jl-bracket-match__participant--winner' : ''; ?>">
										<span class="jl-bracket-match__name" data-entry="<?php echo (int) $participant->project_entry_id; ?>"><?php echo htmlspecialchars($participant->name, ENT_QUOTES, 'UTF-8'); ?></span>
										<span><?php echo htmlspecialchars($formatValue($participant->value), ENT_QUOTES, 'UTF-8'); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<?php
		// Land on the requested stage, not the top-left corner: earlier,
		// larger qualifying rounds pull that stage's vertical position away
		// from y=0 (see BracketModel::getBracket()), so without this the
		// page can load looking empty until the visitor steps back to find it.
		$focusRoundIndex = (int) ($bracket['focus_round_index'] ?? 0);
		$focusRound = $bracket['rounds'][$focusRoundIndex] ?? null;
		$focusYs = $focusRound ? array_map(static fn (object $item): float => (float) $item->y, $focusRound['items']) : [];
		$focusY = $focusYs !== [] ? array_sum($focusYs) / count($focusYs) : 0;
		$roundNames = array_column($bracket['rounds'], 'name');
		?>
		<script>
			(function () {
				var scroller = document.getElementById('jl-bracket-scroll');
				var prevBtn = document.getElementById('jl-bracket-prev');
				var nextBtn = document.getElementById('jl-bracket-next');
				var currentLabel = document.getElementById('jl-bracket-current');
				var columnWidth = <?php echo $columnWidth; ?>;
				var roundCount = <?php echo $roundCount; ?>;
				var roundNames = <?php echo json_encode($roundNames, JSON_UNESCAPED_UNICODE); ?>;
				var current = <?php echo $focusRoundIndex; ?>;

				// scroll-behavior:smooth turns a plain "scrollLeft = x" assignment into an
				// animation, which reads back as unchanged until the next frame — fine for
				// button clicks (scrollTo({behavior:'smooth'}) below), but the very first
				// positioning on page load must land instantly, so it's done with smooth
				// scrolling explicitly switched off for that one assignment.
				function render(smooth) {
					var target = current * columnWidth - 16;
					if (smooth) {
						scroller.scrollTo({ left: target, behavior: 'smooth' });
					} else {
						scroller.style.scrollBehavior = 'auto';
						scroller.scrollLeft = target;
						scroller.style.scrollBehavior = '';
					}
					currentLabel.textContent = roundNames[current] || '';
					prevBtn.disabled = current <= 0;
					nextBtn.disabled = current >= roundCount - 1;
				}

				prevBtn.addEventListener('click', function () { current = Math.max(0, current - 1); render(true); });
				nextBtn.addEventListener('click', function () { current = Math.min(roundCount - 1, current + 1); render(true); });

				render(false);
				scroller.scrollTop = Math.max(0, <?php echo (int) $focusY; ?> - scroller.clientHeight / 2 + <?php echo $cardHeight / 2; ?>);

				// Click a participant anywhere in the bracket to trace its whole path:
				// every programme-item card containing that entry is highlighted, and any
				// connector joining two highlighted items lights up too. Click the same entry
				// again to clear it. The stable project-entry ID avoids name collisions.
				var canvas = document.querySelector('.jl-bracket-canvas');
				var selectedEntry = null;

				function applyHighlight() {
					var matchIds = {};
					canvas.querySelectorAll('.jl-bracket-match__name[data-entry]').forEach(function (nameEl) {
						var active = selectedEntry !== null && nameEl.getAttribute('data-entry') === selectedEntry;
						nameEl.classList.toggle('jl-bracket-match__name--active', active);
						if (active) {
							matchIds[nameEl.closest('.jl-bracket-match').getAttribute('data-id')] = true;
						}
					});
					canvas.querySelectorAll('.jl-bracket-match').forEach(function (card) {
						card.classList.toggle('jl-bracket-match--active', !!matchIds[card.getAttribute('data-id')]);
					});
					canvas.querySelectorAll('.jl-bracket-svg line').forEach(function (line) {
						var active = matchIds[line.getAttribute('data-to')] && matchIds[line.getAttribute('data-from')];
						line.setAttribute('stroke', active ? '#0d6efd' : '#adb5bd');
						line.setAttribute('stroke-width', active ? 3 : 2);
					});
				}

				canvas.addEventListener('click', function (event) {
					var nameEl = event.target.closest('.jl-bracket-match__name[data-entry]');
					if (!nameEl) {
						return;
					}
					var entry = nameEl.getAttribute('data-entry');
					selectedEntry = selectedEntry === entry ? null : entry;
					applyHighlight();
				});
			})();
		</script>
	<?php endif; ?>
</div>
