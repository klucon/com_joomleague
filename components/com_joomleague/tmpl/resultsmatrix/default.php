<?php

/**
 * Křížová tabulka výsledků (result matrix) — plná parita se starou view.
 *
 * @author   Ondřej Klučka
 * @package  Klucon.Joomleague
 * @license  GNU General Public License version 2 or later
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Joomleague\Component\Joomleague\Site\View\Resultsmatrix\HtmlView $this */

$project = $this->project;

// odkaz na tým (řádek i sloupec hlavičky)
$teamLink = static fn (int $ptid): string =>
	Route::_('index.php?option=com_joomleague&view=team&id=' . $ptid);

/**
 * Vykreslí obsah jedné buňky (může obsahovat víc výsledků).
 *
 * @param  array  $results  výsledky z modelu pro dvojici (řádek = domácí, sloupec = host)
 */
$renderCell = static function (array $results): string {
	$out = [];

	foreach ($results as $r) {
		// zrušený zápas
		if ($r->cancel) {
			$reason = $r->cancel_reason ? htmlspecialchars((string) $r->cancel_reason, ENT_QUOTES, 'UTF-8') : Text::_('COM_JOOMLEAGUE_SITE_MATCH_CANCELLED');
			$cell   = '<span class="text-muted" title="' . $reason . '">&#10007;</span>';
			if ($r->new_match_id > 0) {
				$cell .= ' <a class="jl-matrix-link" href="' . Route::_('index.php?option=com_joomleague&view=matchreport&id=' . $r->new_match_id) . '" title="' . Text::_('COM_JOOMLEAGUE_SITE_NEW_MATCH') . '">&#8594;</a>';
			}
			$out[] = $cell;
			continue;
		}

		// kontumace / rozhodnutí (decision != 0)
		if ($r->decision) {
			$v1  = $r->v1 === null || $r->v1 === '' ? 'X' : (string) (int) $r->v1;
			$v2  = $r->v2 === null || $r->v2 === '' ? 'X' : (string) (int) $r->v2;
			$cls = 'text-secondary';
			if ($v1 !== 'X' && $v2 !== 'X') {
				$cls = $v1 > $v2 ? 'fw-bold text-success' : ($v1 < $v2 ? 'text-danger' : 'text-secondary');
			}
			$out[] = '<a class="jl-matrix-link ' . $cls . '" href="' . Route::_('index.php?option=com_joomleague&view=matchreport&id=' . $r->id) . '" title="' . Text::_('COM_JOOMLEAGUE_SITE_DECISION') . '">' . $v1 . ':' . $v2 . '</a>';
			continue;
		}

		// řádně odehraný zápas se skóre
		if ($r->played) {
			$e1     = (int) $r->home_result;
			$e2     = (int) $r->away_result;
			$cls    = $e1 > $e2 ? 'fw-bold text-success' : ($e1 < $e2 ? 'text-danger' : 'text-secondary');
			$suffix = '';
			if ($r->rtype === 1) {
				$suffix = ' <small class="text-muted">' . Text::_('COM_JOOMLEAGUE_SITE_OVERTIME') . '</small>';
			} elseif ($r->rtype === 2) {
				$suffix = ' <small class="text-muted">' . Text::_('COM_JOOMLEAGUE_SITE_SHOOTOUT') . '</small>';
			}
			$out[] = '<a class="jl-matrix-link ' . $cls . '" href="' . Route::_('index.php?option=com_joomleague&view=matchreport&id=' . $r->id) . '">' . $e1 . ':' . $e2 . $suffix . '</a>';
			continue;
		}

		// naplánovaný, ještě neodehraný — nezaměnitelný puntík s kolem v tooltipu
		$title = $r->round_name ? (string) $r->round_name : ($r->round_code !== null && $r->round_code !== '' ? Text::_('COM_JOOMLEAGUE_SITE_ROUND') . ' ' . $r->round_code : '');
		$title = $title !== '' ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : Text::_('COM_JOOMLEAGUE_SITE_NOT_PLAYED');
		$out[] = '<a class="jl-matrix-link text-muted" href="' . Route::_('index.php?option=com_joomleague&view=matchreport&id=' . $r->id) . '" title="' . $title . '">&#183;</a>';
	}

	return $out ? implode('<br>', $out) : '<span class="text-muted">&nbsp;</span>';
};

// rozdělení týmů do divizí (víc divizí = víc matic)
$divisions = $this->divisions ?? [];
$groups    = [];

if (count($divisions) > 1) {
	foreach ($divisions as $div) {
		$divTeams = array_values(array_filter($this->teams, static fn ($t) => (int) ($t->division_id ?? 0) === (int) $div->id));
		if ($divTeams) {
			$groups[] = (object) ['title' => $div->name, 'teams' => $divTeams];
		}
	}
	$noDiv = array_values(array_filter($this->teams, static fn ($t) => (int) ($t->division_id ?? 0) === 0));
	if ($noDiv) {
		$groups[] = (object) ['title' => '', 'teams' => $noDiv];
	}
} else {
	$groups[] = (object) ['title' => '', 'teams' => array_values($this->teams)];
}
?>
<div class="com-joomleague-site">
	<?php if (!$project) : ?>
		<div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT_NOT_FOUND'); ?></div>
		<?php return; ?>
	<?php endif; ?>

	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->escape($project->name); ?></div>
		<h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULT_MATRIX'); ?></h1>
	</section>

	<?php if (!$this->teams) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div>
	<?php else : ?>
		<?php foreach ($groups as $group) : ?>
			<?php if ($group->title !== '') : ?>
				<h2 class="jl-site-subtitle h5 mt-4 mb-2"><?php echo $this->escape($group->title); ?></h2>
			<?php endif; ?>
			<div class="jl-site-panel table-responsive mb-3">
				<table class="table jl-site-table jl-matrix align-middle text-center">
					<thead>
						<tr>
							<th class="text-start"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th>
							<?php foreach ($group->teams as $col) : ?>
								<th scope="col" title="<?php echo $this->escape($col->team_name); ?>">
									<a href="<?php echo $teamLink((int) $col->id); ?>"><?php echo $this->escape($col->team_short_name ?: $col->team_name); ?></a>
								</th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($group->teams as $row) : ?>
							<tr>
								<th scope="row" class="text-start">
									<a href="<?php echo $teamLink((int) $row->id); ?>"><?php echo $this->escape($row->team_name); ?></a>
								</th>
								<?php foreach ($group->teams as $col) : ?>
									<?php if ((int) $row->id === (int) $col->id) : ?>
										<td class="jl-matrix-self">&#9679;</td>
									<?php else : ?>
										<td><?php echo $renderCell($this->matrix[(int) $row->id][(int) $col->id] ?? []); ?></td>
									<?php endif; ?>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endforeach; ?>

		<p class="jl-site-muted small mt-2"><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATRIX_HINT'); ?></p>
	<?php endif; ?>
</div>
