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
$cardHeight = 52;
$columnGap = 40;
$columnWidth = $cardWidth + $columnGap;
$roundCount = isset($bracket['rounds']) ? count($bracket['rounds']) : 0;

$formatScore = static function (?float $home, ?float $away): string {
    if ($home === null || $away === null) {
        return Text::_('JNONE');
    }
    $format = static fn (float $value): string => rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    return $format($home) . ':' . $format($away);
};
?>
<div class="com-joomleague-bracket">
	<?php if (isset($bracket['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($bracket['error']); ?></div>
	<?php else : ?>
		<h1 class="com-joomleague-bracket__title"><?php echo htmlspecialchars((string) $bracket['project']->name, ENT_QUOTES, 'UTF-8'); ?></h1>

		<style>
			.jl-bracket-nav { display: flex; align-items: center; gap: .5rem; margin-bottom: .5rem; }
			.jl-bracket-nav__btn { border: 1px solid #dee2e6; background: #fff; border-radius: .375rem; width: 2.25rem; height: 2.25rem; font-size: 1.1rem; line-height: 1; cursor: pointer; }
			.jl-bracket-nav__btn:disabled { opacity: .35; cursor: default; }
			.jl-bracket-nav__btn:not(:disabled):hover { background: #f1f3f5; }
			.jl-bracket-viewport { overflow: hidden; border: 1px solid #e9ecef; border-radius: .375rem; }
			.jl-bracket-scroll { overflow-y: auto; overflow-x: auto; max-height: 75vh; scroll-behavior: smooth; }
			/* Horizontal movement is arrow-driven (see #jl-bracket-prev/next below), so the
			   horizontal scrollbar itself is just visual clutter — hidden where CSS allows
			   hiding a single axis (WebKit/Blink); Firefox still shows it, which is a harmless
			   fallback since scrollLeft keeps working either way, just with a visible bar. */
			.jl-bracket-scroll::-webkit-scrollbar:horizontal { display: none; }
			.jl-bracket-canvas { position: relative; }
			.jl-bracket-header { position: sticky; top: 0; z-index: 2; display: flex; background: #fff; border-bottom: 1px solid #dee2e6; }
			.jl-bracket-header__col { flex: 0 0 <?php echo $columnWidth; ?>px; text-align: center; font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; color: #6c757d; padding: .5rem 0; }
			.jl-bracket-svg { position: absolute; top: 0; left: 0; pointer-events: none; }
			.jl-bracket-match { position: absolute; background: #fff; border: 1px solid #dee2e6; border-radius: .375rem; padding: .3rem .55rem; font-size: .78rem; line-height: 1.25; box-sizing: border-box; overflow: hidden; }
			.jl-bracket-match--active { border-color: #0d6efd; box-shadow: inset 0 0 0 1px #0d6efd; }
			.jl-bracket-match__team { display: flex; justify-content: space-between; gap: .4rem; }
			.jl-bracket-match__team--winner { font-weight: 700; }
			.jl-bracket-match__name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: pointer; border-radius: .2rem; }
			.jl-bracket-match__name:hover { background: #f1f3f5; }
			.jl-bracket-match__name--active { color: #0d6efd; text-decoration: underline; }
			.jl-bracket-match__shootout { display: block; font-size: .68rem; color: #6c757d; text-align: right; }
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
							<?php foreach ($round['matches'] as $match) : ?>
								<?php foreach ($match['feeder_ids'] as $i => $feederId) : ?>
									<line
										data-to="<?php echo $match['id']; ?>" data-from="<?php echo $feederId; ?>"
										x1="<?php echo ($roundIndex - 1) * $columnWidth + $cardWidth; ?>"
										y1="<?php echo $match['feeder_ys'][$i] + $cardHeight / 2; ?>"
										x2="<?php echo $roundIndex * $columnWidth; ?>"
										y2="<?php echo $match['y'] + $cardHeight / 2; ?>"
										stroke="#adb5bd" stroke-width="2"
									/>
								<?php endforeach; ?>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</svg>

					<?php foreach ($bracket['rounds'] as $roundIndex => $round) : ?>
						<?php foreach ($round['matches'] as $match) : ?>
							<div class="jl-bracket-match" data-id="<?php echo $match['id']; ?>" style="left:<?php echo $roundIndex * $columnWidth; ?>px;top:<?php echo $match['y']; ?>px;width:<?php echo $cardWidth; ?>px;height:<?php echo $cardHeight; ?>px;">
								<div class="jl-bracket-match__team<?php echo $match['winner'] === 'home' ? ' jl-bracket-match__team--winner' : ''; ?>">
									<?php if ($match['home'] !== '') : ?>
										<span class="jl-bracket-match__name" data-team="<?php echo htmlspecialchars($match['home'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($match['home'], ENT_QUOTES, 'UTF-8'); ?></span>
									<?php else : ?>
										<span class="jl-bracket-match__name">—</span>
									<?php endif; ?>
									<span><?php echo $match['home_score'] !== null ? (int) $match['home_score'] : ''; ?></span>
								</div>
								<div class="jl-bracket-match__team<?php echo $match['winner'] === 'away' ? ' jl-bracket-match__team--winner' : ''; ?>">
									<?php if ($match['away'] !== '') : ?>
										<span class="jl-bracket-match__name" data-team="<?php echo htmlspecialchars($match['away'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($match['away'], ENT_QUOTES, 'UTF-8'); ?></span>
									<?php else : ?>
										<span class="jl-bracket-match__name">—</span>
									<?php endif; ?>
									<span><?php echo $match['away_score'] !== null ? (int) $match['away_score'] : ''; ?></span>
								</div>
								<?php if ($match['home_shootout'] !== null && $match['away_shootout'] !== null) : ?>
									<span class="jl-bracket-match__shootout">(<?php echo Text::_('COM_JOOMLEAGUE_RESULTS_SHOOTOUT_LABEL'); ?> <?php echo $formatScore($match['home_shootout'], $match['away_shootout']); ?>)</span>
								<?php endif; ?>
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
		$focusYs = $focusRound ? array_column($focusRound['matches'], 'y') : [];
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

				// Click a team's name anywhere in the bracket to trace its whole path:
				// every match card the team appears in gets highlighted, and any connector
				// line joining two highlighted matches lights up too. Click the same team
				// again (or elsewhere) to clear it. Deliberately scoped to the team NAME,
				// not the whole match card — the card itself is reserved for a future
				// click-through to the match detail page.
				var canvas = document.querySelector('.jl-bracket-canvas');
				var selectedTeam = null;

				function applyHighlight() {
					var matchIds = {};
					canvas.querySelectorAll('.jl-bracket-match__name[data-team]').forEach(function (nameEl) {
						var active = selectedTeam !== null && nameEl.getAttribute('data-team') === selectedTeam;
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
					var nameEl = event.target.closest('.jl-bracket-match__name[data-team]');
					if (!nameEl) {
						return;
					}
					var team = nameEl.getAttribute('data-team');
					selectedTeam = selectedTeam === team ? null : team;
					applyHighlight();
				});
			})();
		</script>
	<?php endif; ?>
</div>
