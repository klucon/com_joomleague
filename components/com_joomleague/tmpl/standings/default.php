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

/** @var Joomleague\Component\Joomleague\Site\View\Standings\HtmlView $this */
$standings = $this->standings;
$translateCode = static function (string $prefix, string $code): string {
	$key = $prefix . strtoupper($code);
	$translated = Text::_($key);

	return $translated === $key ? ucwords(str_replace('_', ' ', $code)) : $translated;
};
?>
<div class="com-joomleague-standings">
	<?php if (isset($standings['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($standings['error']); ?></div>
	<?php else : ?>
		<h1 class="com-joomleague-standings__title"><?php echo htmlspecialchars((string) $standings['project']->name, ENT_QUOTES, 'UTF-8'); ?></h1>
		<p class="text-body-secondary mb-3">
			<?php echo htmlspecialchars($translateCode('COM_JOOMLEAGUE_STANDINGS_TYPE_', (string) $standings['standings_type']), ENT_QUOTES, 'UTF-8'); ?>
			<?php if ($standings['stage'] !== null) : ?>
				<span aria-hidden="true"> · </span><?php echo htmlspecialchars((string) $standings['stage']->name, ENT_QUOTES, 'UTF-8'); ?>
			<?php endif; ?>
		</p>
		<?php if (\count($standings['available_scopes']) > 1) : ?>
			<nav class="nav nav-tabs mb-3" aria-label="<?php echo htmlspecialchars(Text::_('COM_JOOMLEAGUE_STANDINGS_SCOPE_NAV_LABEL'), ENT_QUOTES, 'UTF-8'); ?>">
				<?php foreach ($standings['available_scopes'] as $availableScope) : ?>
					<a class="nav-link<?php echo $availableScope === $standings['scope'] ? ' active' : ''; ?>"
						href="<?php echo Route::_('index.php?option=com_joomleague&view=standings&project_id=' . (int) $standings['project']->id . ($standings['stage'] !== null ? '&stage_id=' . (int) $standings['stage']->id : '') . '&scope=' . rawurlencode((string) $availableScope)); ?>"
						<?php echo $availableScope === $standings['scope'] ? ' aria-current="page"' : ''; ?>><?php echo htmlspecialchars($translateCode('COM_JOOMLEAGUE_STANDINGS_SCOPE_', (string) $availableScope), ENT_QUOTES, 'UTF-8'); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
		<?php
		$columns = $standings['columns'];
		$columnCount = \count($columns);
		$shortLabels = (bool) ($standings['short_labels'] ?? false);
		$shortLabelTooltips = $shortLabels && (bool) ($standings['short_label_tooltips'] ?? true);
		$responsiveColumns = (bool) ($standings['responsive_columns'] ?? false);
		$formEnabled = (bool) ($standings['form_enabled'] ?? false);
		$form = $standings['form'] ?? [];

		// Text::_() returns the key unchanged when it isn't defined — used
		// here to fall back from a "_SHORT"/"_COMBINED" variant to the base
		// metric label instead of ever printing a raw, untranslated key.
		$resolveLabel = static function (string $key): ?string {
			$text = Text::_($key);
			return $text === $key ? null : $text;
		};
		$columnBaseKey = static fn (array $column): string => 'COM_JOOMLEAGUE_STANDING_METRIC_' . strtoupper($column['type'] === 'combined' ? $column['prefix'] . '_combined' : $column['code']);
		$columnFullLabel = static function (array $column) use ($resolveLabel, $columnBaseKey): string {
			$base = $columnBaseKey($column);
			return $resolveLabel($base) ?? $base;
		};
		$columnLabel = static function (array $column) use ($shortLabels, $resolveLabel, $columnBaseKey, $columnFullLabel): string {
			if ($shortLabels) {
				$short = $resolveLabel($columnBaseKey($column) . '_SHORT');
				if ($short !== null) {
					return $short;
				}
			}
			return $columnFullLabel($column);
		};
		// Only worth a tooltip when the short form actually differs from the
		// full label — no point hovering "Hráno" to learn it means "Hráno".
		$columnTooltipAttr = static function (array $column) use ($shortLabelTooltips, $columnLabel, $columnFullLabel): string {
			if (!$shortLabelTooltips) {
				return '';
			}
			$short = $columnLabel($column);
			$full = $columnFullLabel($column);
			return $short !== $full ? ' title="' . htmlspecialchars($full, ENT_QUOTES, 'UTF-8') . '"' : '';
		};

		$formHeaderFull = Text::_('COM_JOOMLEAGUE_STANDINGS_COLUMN_FORM');
		$formHeaderLabel = $shortLabels ? ($resolveLabel('COM_JOOMLEAGUE_STANDINGS_COLUMN_FORM_SHORT') ?? $formHeaderFull) : $formHeaderFull;
		$formHeaderTooltip = ($shortLabelTooltips && $formHeaderLabel !== $formHeaderFull) ? ' title="' . htmlspecialchars($formHeaderFull, ENT_QUOTES, 'UTF-8') . '"' : '';

		// Responsive mode keeps rank, entry, form and only the LAST column
		// always visible; earlier columns collapse below the "md" breakpoint
		// instead of forcing the table to scroll sideways.
		$responsiveClass = static fn (int $columnIndex): string => $responsiveColumns && $columnIndex < $columnCount - 1 ? ' d-none d-md-table-cell' : '';
		$formatValue = static function (mixed $value, array $column): string {
			if ($value === null) {
				return Text::_('JNONE');
			}

			$code = (string) ($column['code'] ?? '');
			if ($code === 'elapsed' && is_numeric($value)) {
				$seconds = max(0, (int) round((float) $value));
				$hours = intdiv($seconds, 3600);
				return sprintf('%d:%02d:%02d', $hours, intdiv($seconds % 3600, 60), $seconds % 60);
			}

			return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
		};
		?>
		<div class="table-responsive">
			<table class="table table-sm table-striped align-middle mb-1">
				<thead>
					<tr>
						<th><?php echo Text::_('COM_JOOMLEAGUE_STANDINGS_COLUMN_RANK'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_STANDINGS_COLUMN_ENTRY'); ?></th>
						<?php foreach ($columns as $i => $column) : ?>
							<th class="text-center<?php echo $responsiveClass($i); ?>"<?php echo $columnTooltipAttr($column); ?>><?php echo htmlspecialchars($columnLabel($column), ENT_QUOTES, 'UTF-8'); ?></th>
						<?php endforeach; ?>
						<?php if ($formEnabled) : ?>
							<th class="text-center"<?php echo $formHeaderTooltip; ?>><?php echo htmlspecialchars($formHeaderLabel, ENT_QUOTES, 'UTF-8'); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					$highlightEntryId = (int) ($standings['highlight_entry_id'] ?? 0);
					$highlightStyle = (string) ($standings['highlight_style'] ?? 'row');
					$highlightColorRow = htmlspecialchars((string) ($standings['highlight_color_row'] ?? '#ffc107'), ENT_QUOTES, 'UTF-8');
					$highlightColorText = htmlspecialchars((string) ($standings['highlight_color_text'] ?? '#000000'), ENT_QUOTES, 'UTF-8');
					$highlightBold = (bool) ($standings['highlight_bold'] ?? false);
					$highlightItalic = (bool) ($standings['highlight_italic'] ?? false);
					$highlightUnderline = (bool) ($standings['highlight_underline'] ?? false);
					$formLabels = ['win' => Text::_('COM_JOOMLEAGUE_STANDINGS_FORM_WIN'), 'draw' => Text::_('COM_JOOMLEAGUE_STANDINGS_FORM_DRAW'), 'loss' => Text::_('COM_JOOMLEAGUE_STANDINGS_FORM_LOSS')];
					$formColor = ['win' => '#198754', 'draw' => '#6c757d', 'loss' => '#dc3545'];
					?>
					<?php foreach ($standings['rows'] as $row) :
						$isHighlighted = $highlightEntryId > 0 && (int) $row->project_entry_id === $highlightEntryId;
						$active = $isHighlighted && $highlightStyle !== 'none';
						// Row background and text colour are independent, simultaneous choices —
						// not two alternate modes. "Whole row" adds a background on top of
						// whatever text colour is set; "Bold text only" never adds a background.
						// Both colours, plus italics/underline, are inherited CSS properties in
						// principle, but table-striped sets background directly on each <td>/<th>
						// (not the <tr>), so everything is applied per-cell explicitly rather
						// than relying on inheritance — see
						// mod_joomleague_standings/tmpl/default.php for the full explanation.
						$parts = [];
						if ($active && $highlightStyle === 'row') {
							$parts[] = 'background-color:' . $highlightColorRow;
						}
						if ($active) {
							$parts[] = 'color:' . $highlightColorText;
						}
						if ($active && $highlightBold) $parts[] = 'font-weight:bold';
						if ($active && $highlightItalic) $parts[] = 'font-style:italic';
						if ($active && $highlightUnderline) $parts[] = 'text-decoration:underline';
						// Zone boundary lines are a border, not a background, and — like the
						// highlight background — table-striped only respects styles set
						// directly on each <td>/<th>, not on the <tr>, so they join the same
						// per-cell style string rather than being set on the row element.
						if (!empty($row->zone_top)) $parts[] = 'border-bottom:4px solid ' . htmlspecialchars((string) $row->zone_top, ENT_QUOTES, 'UTF-8');
						if (!empty($row->zone_bottom)) $parts[] = 'border-top:4px solid ' . htmlspecialchars((string) $row->zone_bottom, ENT_QUOTES, 'UTF-8');
						$cellStyle = $parts !== [] ? implode(';', $parts) . ';' : '';
						// <th> is bold by browser default regardless of any class/style the row
						// carries, so its weight must always be stated explicitly in both
						// directions, not just when the "Bold" decoration is on.
						$entryStyle = 'font-weight:' . (($active && $highlightBold) ? 'bold' : 'normal') . ';' . $cellStyle;
					?>
						<tr>
							<td<?php echo $cellStyle !== '' ? ' style="' . $cellStyle . '"' : ''; ?>><?php echo (int) $row->rank_number; ?></td>
							<th scope="row" style="<?php echo $entryStyle; ?>"><?php echo htmlspecialchars((string) $row->entry_name_snapshot, ENT_QUOTES, 'UTF-8'); ?></th>
							<?php foreach ($columns as $i => $column) :
								if ($column['type'] === 'combined') {
									$forValue = $row->metrics[$column['for']] ?? null;
									$againstValue = $row->metrics[$column['against']] ?? null;
									$cellText = ($forValue === null || $againstValue === null)
										? Text::_('JNONE')
										: htmlspecialchars((string) $forValue, ENT_QUOTES, 'UTF-8') . ':' . htmlspecialchars((string) $againstValue, ENT_QUOTES, 'UTF-8');
								} else {
									$value = $row->metrics[$column['code']] ?? null;
									$cellText = $formatValue($value, $column);
								}
							?>
								<td class="text-center<?php echo $responsiveClass($i); ?>"<?php echo $cellStyle !== '' ? ' style="' . $cellStyle . '"' : ''; ?>><?php echo $cellText; ?></td>
							<?php endforeach; ?>
							<?php if ($formEnabled) : ?>
								<td class="text-center"<?php echo $cellStyle !== '' ? ' style="' . $cellStyle . '"' : ''; ?>>
									<?php foreach ($form[(int) $row->project_entry_id] ?? [] as $outcome) : ?>
										<span style="display:inline-block;min-width:1.4em;margin:0 1px;border-radius:3px;color:#fff;font-size:0.75em;font-weight:bold;background-color:<?php echo $formColor[$outcome]; ?>;"><?php echo $formLabels[$outcome]; ?></span>
									<?php endforeach; ?>
								</td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
