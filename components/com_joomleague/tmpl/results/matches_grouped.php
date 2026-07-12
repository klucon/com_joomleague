<?php

/**
 * Sdílený výpis zápasů seskupených po kolech — oddělené rámečky, pevné šířky sloupců.
 * Očekává $this->matches (objekty z getMatches). Použití: require tohoto souboru.
 *
 * @author   Ondřej Klučka
 * @package  Klucon.Joomleague
 * @license  GNU General Public License version 2 or later
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$jlMatchRow = static function ($m): void {
	$score = $m->team1_result === null || $m->team2_result === null
		? '<span class="jl-site-muted">' . Text::_('COM_JOOMLEAGUE_SITE_NOT_PLAYED') . '</span>'
		: '<span class="jl-site-score">' . htmlspecialchars((string) $m->team1_result . ' : ' . (string) $m->team2_result, ENT_QUOTES, 'UTF-8') . '</span>';
	?>
	<tr>
		<td class="text-nowrap"><?php echo $m->match_date && strpos((string) $m->match_date, '0000-00-00') !== 0 ? htmlspecialchars(date('d.m.Y H:i', strtotime((string) $m->match_date)), ENT_QUOTES, 'UTF-8') : ''; ?></td>
		<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $m->home_projectteam_id); ?>"><?php echo htmlspecialchars((string) ($m->home_name ?? ''), ENT_QUOTES, 'UTF-8'); ?></a></td>
		<td class="text-center"><?php echo $score; ?></td>
		<td><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $m->away_projectteam_id); ?>"><?php echo htmlspecialchars((string) ($m->away_name ?? ''), ENT_QUOTES, 'UTF-8'); ?></a></td>
		<td><?php echo htmlspecialchars((string) ($m->playground_name ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
		<td class="text-end"><a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchreport&id=' . (int) $m->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></a></td>
	</tr>
	<?php
};

$jlColgroup = '<colgroup>'
	. '<col style="width:15%">'
	. '<col style="width:22%">'
	. '<col style="width:8%">'
	. '<col style="width:22%">'
	. '<col style="width:23%">'
	. '<col style="width:10%">'
	. '</colgroup>';

$jlTableHead = '<thead><tr>'
	. '<th>' . Text::_('COM_JOOMLEAGUE_SITE_DATE') . '</th>'
	. '<th>' . Text::_('COM_JOOMLEAGUE_SITE_HOME') . '</th>'
	. '<th class="text-center">' . Text::_('COM_JOOMLEAGUE_SITE_SCORE') . '</th>'
	. '<th>' . Text::_('COM_JOOMLEAGUE_SITE_AWAY') . '</th>'
	. '<th>' . Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND') . '</th><th></th></tr></thead>';

$jlByRound = [];
foreach ($this->matches as $jlM) {
	$jlRoundKey = (string) ((int) ($jlM->round_id ?? 0) ?: ($jlM->roundcode ?? $jlM->round_name ?? ''));
	$jlRoundName = trim((string) ($jlM->round_name ?? ''));
	$jlRoundCode = (int) ($jlM->roundcode ?? 0);
	$jlMatchDate = (string) ($jlM->match_date ?? '');
	$jlMatchTime = $jlMatchDate !== '' && strpos($jlMatchDate, '0000-00-00') !== 0 ? strtotime($jlMatchDate) : false;

	if (!isset($jlByRound[$jlRoundKey])) {
		$jlByRound[$jlRoundKey] = [
			'name' => $jlRoundName,
			'code' => $jlRoundCode,
			'sort' => $jlMatchTime !== false ? (int) $jlMatchTime : PHP_INT_MAX,
			'matches' => [],
		];
	}

	if ($jlMatchTime !== false) {
		$jlByRound[$jlRoundKey]['sort'] = min((int) $jlByRound[$jlRoundKey]['sort'], (int) $jlMatchTime);
	}

	$jlByRound[$jlRoundKey]['matches'][] = $jlM;
}

uasort($jlByRound, static function (array $a, array $b): int {
	$codeA = (int) ($a['sort'] ?? PHP_INT_MAX);
	$codeB = (int) ($b['sort'] ?? PHP_INT_MAX);

	if ($codeA !== $codeB) {
		return $codeA <=> $codeB;
	}

	return ((int) ($a['code'] ?? 0) <=> (int) ($b['code'] ?? 0))
		?: strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
});
?>
<?php if (!$this->matches) : ?>
	<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_MATCHES'); ?></div>
<?php else : ?>
	<?php foreach ($jlByRound as $jlRound) : ?>
		<?php $jlRoundName = (string) ($jlRound['name'] ?? ''); ?>
		<div class="jl-schedule-round">
			<?php if ($jlRoundName !== '') : ?>
				<div class="jl-schedule-round__head"><?php echo $this->escape($jlRoundName); ?></div>
			<?php endif; ?>
			<div class="table-responsive">
				<table class="table jl-site-table jl-matches-table align-middle mb-0">
					<?php echo $jlColgroup; ?>
					<?php echo $jlTableHead; ?>
					<tbody><?php foreach (($jlRound['matches'] ?? []) as $jlM) { $jlMatchRow($jlM); } ?></tbody>
				</table>
			</div>
		</div>
	<?php endforeach; ?>
<?php endif; ?>
