<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Forma z posledních zápasů (sloupec LASTGAMES v tabulce pořadí) – ikony V/R/P
 * s tooltipem na skóre a odkazem na report zápasu, nejnovější první.
 *
 * @var array $displayData ['projectId' => int, 'projectTeamId' => int, 'limit' => int, 'model' => object]
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

$projectId = (int) ($displayData['projectId'] ?? 0);
$projectTeamId = (int) ($displayData['projectTeamId'] ?? 0);
$limit = (int) ($displayData['limit'] ?? 5) ?: 5;
$model = $displayData['model'] ?? null;

if ($projectId < 1 || $projectTeamId < 1 || $model === null) {
	return;
}

$played = [];

foreach ($model->getMatches($projectId, 0, $projectTeamId) as $match) {
	if ($match->team1_result === null || $match->team2_result === null) {
		continue;
	}

	$isHome = (int) ($match->home_projectteam_id ?? 0) === $projectTeamId;
	$own = (float) ($isHome ? $match->team1_result : $match->team2_result);
	$against = (float) ($isHome ? $match->team2_result : $match->team1_result);

	$match->form_opponent = (string) ($isHome ? ($match->away_name ?? '') : ($match->home_name ?? ''));
	$match->form_score = rtrim(rtrim(number_format($own, 1), '0'), '.') . ':' . rtrim(rtrim(number_format($against, 1), '0'), '.');
	$match->form_result = $own > $against ? 'w' : ($own < $against ? 'l' : 'd');
	$played[] = $match;
}

usort($played, static fn ($a, $b) => strcmp((string) $b->match_date, (string) $a->match_date));
$played = array_slice($played, 0, $limit);
?>
<?php if ($played === []) : ?>
	<span class="jl-site-muted">—</span>
<?php else : ?>
	<span class="jl-site-form">
		<?php foreach (array_reverse($played) as $match) : ?>
			<a
				class="jl-site-form__icon jl-site-form__icon--<?php echo $match->form_result; ?>"
				href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $match->id); ?>"
				title="<?php echo htmlspecialchars($match->form_opponent . ' (' . $match->form_score . ')', ENT_QUOTES, 'UTF-8'); ?>"
			><?php echo strtoupper($match->form_result); ?></a>
		<?php endforeach; ?>
	</span>
<?php endif; ?>
