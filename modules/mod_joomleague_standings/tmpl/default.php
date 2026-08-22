<?php

/**
 * @package     Joomleague.Site
 * @subpackage  mod_joomleague_standings
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/** @var array{error?:string,project?:object,columns?:array,snapshot?:object,rows?:array} $standings */
$moduleclassSfx = htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
?>
<div class="mod-joomleague-standings<?php echo $moduleclassSfx; ?>">
	<?php if (isset($standings['error'])) : ?>
		<div class="alert alert-warning mb-0"><?php echo Text::_($standings['error']); ?></div>
	<?php else : ?>
		<?php if ((int) $params->get('show_project_name', 1) === 1 && isset($standings['project'])) : ?>
			<div class="mod-joomleague-standings__title fw-bold mb-2"><?php echo htmlspecialchars((string) $standings['project']->name, ENT_QUOTES, 'UTF-8'); ?></div>
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

		$formHeaderFull = Text::_('MOD_JOOMLEAGUE_STANDINGS_COLUMN_FORM');
		$formHeaderLabel = $shortLabels ? ($resolveLabel('MOD_JOOMLEAGUE_STANDINGS_COLUMN_FORM_SHORT') ?? $formHeaderFull) : $formHeaderFull;
		$formHeaderTooltip = ($shortLabelTooltips && $formHeaderLabel !== $formHeaderFull) ? ' title="' . htmlspecialchars($formHeaderFull, ENT_QUOTES, 'UTF-8') . '"' : '';

		// Responsive mode keeps rank, entry, form and only the LAST column
		// always visible; earlier columns collapse below the "md" breakpoint
		// instead of forcing the table to scroll sideways.
		$responsiveClass = static fn (int $columnIndex): string => $responsiveColumns && $columnIndex < $columnCount - 1 ? ' d-none d-md-table-cell' : '';
		?>
		<div class="table-responsive">
			<table class="table table-sm table-striped align-middle mb-1">
				<thead>
					<tr>
						<th><?php echo Text::_('MOD_JOOMLEAGUE_STANDINGS_COLUMN_RANK'); ?></th>
						<th><?php echo Text::_('MOD_JOOMLEAGUE_STANDINGS_COLUMN_ENTRY'); ?></th>
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
					$formLabels = ['win' => Text::_('MOD_JOOMLEAGUE_STANDINGS_FORM_WIN'), 'draw' => Text::_('MOD_JOOMLEAGUE_STANDINGS_FORM_DRAW'), 'loss' => Text::_('MOD_JOOMLEAGUE_STANDINGS_FORM_LOSS')];
					$formColor = ['win' => '#198754', 'draw' => '#6c757d', 'loss' => '#dc3545'];
					?>
					<?php foreach ($standings['rows'] as $row) :
						$isHighlighted = $highlightEntryId > 0 && (int) $row->project_entry_id === $highlightEntryId;
						$active = $isHighlighted && $highlightStyle !== 'none';
						// Row background and text colour are independent, simultaneous choices —
						// not two alternate modes. "Whole row" adds a background on top of
						// whatever text colour is set; "Bold text only" never adds a background.
						// Background-colour and zone border lines still have to go on every
						// <td>/<th> explicitly: table-striped sets it directly on each cell, not
						// the <tr>, so an inline style there would just be overridden on odd rows.
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
						if (!empty($row->zone_top)) $parts[] = 'border-bottom:4px solid ' . htmlspecialchars((string) $row->zone_top, ENT_QUOTES, 'UTF-8');
						if (!empty($row->zone_bottom)) $parts[] = 'border-top:4px solid ' . htmlspecialchars((string) $row->zone_bottom, ENT_QUOTES, 'UTF-8');
						$cellStyle = $parts !== [] ? implode(';', $parts) . ';' : '';
						// <th> is bold by browser default regardless of the <tr>'s own class —
						// unlike the <td> cells, which correctly inherit fw-bold from the row,
						// the entry cell needs its weight stated explicitly in both directions.
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
									$cellText = $value === null ? Text::_('JNONE') : htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
