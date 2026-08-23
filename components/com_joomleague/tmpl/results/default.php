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
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Results\HtmlView $this */
$results = $this->results;

$formatScore = static function ($home, $away): string {
    if ($home === null || $away === null) {
        return Text::_('JNONE');
    }
    $format = static fn ($value): string => rtrim(rtrim((string) $value, '0'), '.');
    return $format($home) . ':' . $format($away);
};

$goalLabel = ['goal' => '', 'penalty_goal' => ' (pen.)', 'own_goal' => ' (vl.)'];
?>
<div class="com-joomleague-results">
	<?php if (isset($results['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($results['error']); ?></div>
	<?php else : ?>
		<h1 class="com-joomleague-results__title"><?php echo htmlspecialchars((string) $results['project']->name, ENT_QUOTES, 'UTF-8'); ?></h1>

		<?php foreach ($results['rounds'] as $round) : ?>
			<?php if ($round['matches'] === []) : continue; endif; ?>
			<h2 class="h5 mt-4"><?php echo htmlspecialchars($round['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
			<div class="list-group mb-3">
				<?php foreach ($round['matches'] as $match) : ?>
					<a class="list-group-item list-group-item-action" href="<?php echo Route::_('index.php?option=com_joomleague&amp;view=programitem&amp;match_id=' . (int) $match['id']); ?>">
						<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
							<div class="com-joomleague-results__teams">
								<span class="fw-bold"><?php echo htmlspecialchars($match['home'], ENT_QUOTES, 'UTF-8'); ?></span>
								&ndash;
								<span class="fw-bold"><?php echo htmlspecialchars($match['away'], ENT_QUOTES, 'UTF-8'); ?></span>
							</div>
							<div class="com-joomleague-results__score">
								<span class="badge bg-secondary fs-6"><?php echo htmlspecialchars($formatScore($match['home_score'], $match['away_score']), ENT_QUOTES, 'UTF-8'); ?></span>
								<?php if ($match['home_shootout'] !== null && $match['away_shootout'] !== null) : ?>
									<small class="text-muted">(<?php echo Text::_('COM_JOOMLEAGUE_RESULTS_SHOOTOUT_LABEL'); ?> <?php echo htmlspecialchars($formatScore($match['home_shootout'], $match['away_shootout']), ENT_QUOTES, 'UTF-8'); ?>)</small>
								<?php endif; ?>
							</div>
						</div>

						<?php if ($match['notes'] !== null || ($match['venue'] !== null && $match['venue'] !== '') || $match['attendance'] !== null) : ?>
							<div class="text-muted small mt-1">
								<?php
								$meta = [];
								if ($match['venue'] !== null && $match['venue'] !== '') {
									$meta[] = htmlspecialchars((string) $match['venue'], ENT_QUOTES, 'UTF-8');
								}
								if ($match['attendance'] !== null) {
									$meta[] = Text::_('COM_JOOMLEAGUE_RESULTS_ATTENDANCE_LABEL') . ': ' . (int) $match['attendance'];
								}
								if ($match['notes'] !== null) {
									$meta[] = htmlspecialchars((string) $match['notes'], ENT_QUOTES, 'UTF-8');
								}
								echo implode(' &middot; ', $meta);
								?>
							</div>
						<?php endif; ?>

						<?php if ($results['show_scorers'] && $match['goals'] !== []) : ?>
							<div class="row small mt-2">
								<div class="col-6">
									<?php foreach ($match['goals'] as $goal) : if ($goal['slot'] !== 1) continue; ?>
										<div><?php echo htmlspecialchars($goal['player'], ENT_QUOTES, 'UTF-8'); ?> <?php echo (int) $goal['minute']; ?>'<?php echo $goalLabel[$goal['type']] ?? ''; ?></div>
									<?php endforeach; ?>
								</div>
								<div class="col-6 text-end">
									<?php foreach ($match['goals'] as $goal) : if ($goal['slot'] !== 2) continue; ?>
										<div><?php echo htmlspecialchars($goal['player'], ENT_QUOTES, 'UTF-8'); ?> <?php echo (int) $goal['minute']; ?>'<?php echo $goalLabel[$goal['type']] ?? ''; ?></div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
