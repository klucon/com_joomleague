<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$data = $this->staffHistory;
$visibleColumns = $data['visible_columns'] ?? array_fill_keys(['played', 'wins', 'draws', 'losses', 'score', 'period'], true);
$menuQuery = !empty($data['menu_id']) ? ['Itemid' => (int) $data['menu_id']] : [];
$sorting = $data['sorting'] ?? ['enabled' => false, 'column' => 'name', 'direction' => 'asc'];
$projectTypeLabel = static function (string $code): string {
	$key = 'COM_JOOMLEAGUE_PROJECT_TYPE_' . strtoupper(str_replace('-', '_', $code));
	$translated = Text::_($key);
	return $translated === $key ? ucwords(str_replace('_', ' ', $code)) : $translated;
};
$sortUrl = static function (string $column) use ($data, $sorting, $menuQuery): string {
	$direction = $sorting['column'] === $column && $sorting['direction'] === 'asc' ? 'desc' : 'asc';
	return Route::_('index.php?' . http_build_query([
		'option' => 'com_joomleague', 'view' => 'clubstaffhistory', 'club_id' => (int) $data['club']->id,
		'filter_team_id' => (int) ($data['filters']['team_id'] ?? 0), 'filter_project_id' => (int) $data['filters']['project_id'], 'filter_season_id' => (int) $data['filters']['season_id'],
		'filter_project_type' => (string) $data['filters']['project_type'], 'filter_search' => (string) $data['pagination_state']['search'],
		'limit' => (int) $data['pagination_state']['requested_limit'], 'sort' => $column, 'direction' => $direction,
	] + $menuQuery));
};
$sortLabel = static function (string $label, string $column) use ($sorting, $sortUrl): string {
	if (!$sorting['enabled']) return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
	$active = $sorting['column'] === $column;
	$icon = $active ? ($sorting['direction'] === 'asc' ? 'icon-arrow-up' : 'icon-arrow-down') : 'icon-sort';
	return '<a class="text-decoration-none text-body d-inline-flex align-items-center gap-1" href="' . $sortUrl($column) . '"><span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span><span class="' . $icon . '" aria-hidden="true"></span></a>';
};
?>
<div class="com-joomleague-clubstaffhistory">
	<?php if (isset($data['error'])) : ?>
		<div class="alert alert-info"><?php echo Text::_($data['error']); ?></div>
	<?php else : ?>
		<header class="border-bottom pb-3 mb-4 d-flex flex-wrap gap-3 align-items-center">
			<?php if ($data['club']->logo) : ?><img src="<?php echo htmlspecialchars((string) $data['club']->logo, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="72" height="72" class="img-fluid"><?php endif; ?>
			<div><h1 class="mb-1"><?php echo Text::sprintf('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_PAGE_TITLE', htmlspecialchars((string) $data['club']->name, ENT_QUOTES, 'UTF-8')); ?></h1><p class="text-body-secondary mb-0"><?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_PAGE_DESC'); ?></p></div>
		</header>
		<form method="get" action="<?php echo Route::_('index.php'); ?>" class="row g-3 align-items-end mb-4">
			<?php if ($menuQuery !== []) : ?><input type="hidden" name="Itemid" value="<?php echo (int) $data['menu_id']; ?>"><?php endif; ?>
			<input type="hidden" name="option" value="com_joomleague"><input type="hidden" name="view" value="clubstaffhistory"><input type="hidden" name="club_id" value="<?php echo (int) $data['club']->id; ?>"><input type="hidden" name="start" value="0"><input type="hidden" name="sort" value="<?php echo htmlspecialchars($sorting['column'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="direction" value="<?php echo htmlspecialchars($sorting['direction'], ENT_QUOTES, 'UTF-8'); ?>">
			<?php echo LayoutHelper::render('history.teamfilter', ['data' => $data, 'id' => 'clubstaffhistory-team', 'all_label' => Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_ALL_TEAMS')], JPATH_COMPONENT . '/layouts'); ?>
			<div class="col-12 col-md-4"><label class="form-label" for="clubstaffhistory-project"><?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_FILTER_PROJECT'); ?></label><select class="form-select" id="clubstaffhistory-project" name="filter_project_id"><option value="0"><?php echo Text::_('JALL'); ?></option><?php foreach ($data['options']['projects'] as $id => $name) : ?><option value="<?php echo (int) $id; ?>"<?php echo (int) $data['filters']['project_id'] === (int) $id ? ' selected' : ''; ?>><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
			<div class="col-12 col-md-3"><label class="form-label" for="clubstaffhistory-season"><?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_FILTER_SEASON'); ?></label><select class="form-select" id="clubstaffhistory-season" name="filter_season_id"><option value="0"><?php echo Text::_('JALL'); ?></option><?php foreach ($data['options']['seasons'] as $id => $name) : ?><option value="<?php echo (int) $id; ?>"<?php echo (int) $data['filters']['season_id'] === (int) $id ? ' selected' : ''; ?>><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
			<div class="col-12 col-md-3"><label class="form-label" for="clubstaffhistory-type"><?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_FILTER_PROJECT_TYPE'); ?></label><select class="form-select" id="clubstaffhistory-type" name="filter_project_type"><option value=""><?php echo Text::_('JALL'); ?></option><?php foreach ($data['options']['project_types'] as $value => $name) : ?><option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $data['filters']['project_type'] === $value ? ' selected' : ''; ?>><?php echo htmlspecialchars($projectTypeLabel($value), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
			<div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_FILTER_APPLY'); ?></button><a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?' . http_build_query(['option' => 'com_joomleague', 'view' => 'clubstaffhistory', 'club_id' => (int) $data['club']->id] + $menuQuery)); ?>" title="<?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_FILTER_RESET'); ?>"><span class="icon-refresh" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_FILTER_RESET'); ?></span></a></div>
			<div class="col-12 col-md-6"><label class="form-label" for="clubstaffhistory-search"><?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_FILTER_SEARCH'); ?></label><input class="form-control" id="clubstaffhistory-search" name="filter_search" type="search" value="<?php echo htmlspecialchars($data['pagination_state']['search'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_FILTER_SEARCH_HINT'); ?>"></div>
			<div class="col-7 col-md-3"><label class="form-label" for="clubstaffhistory-limit"><?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_PAGE_SIZE'); ?></label><select class="form-select" id="clubstaffhistory-limit" name="limit" onchange="this.form.submit()"><?php foreach ([10, 25, 50, 100, 0] as $size) : ?><option value="<?php echo $size; ?>"<?php echo (int) $data['pagination_state']['requested_limit'] === $size ? ' selected' : ''; ?>><?php echo $size === 0 ? Text::_('JALL') : $size; ?></option><?php endforeach; ?></select></div>
			<div class="col-5 col-md-3 text-end text-body-secondary pb-2"><?php echo Text::sprintf('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_RANGE', $data['pagination_state']['total'] ? $data['pagination_state']['start'] + 1 : 0, min($data['pagination_state']['start'] + $data['pagination_state']['limit'], $data['pagination_state']['total']), $data['pagination_state']['total']); ?></div>
		</form>
		<?php if ($data['managers'] === []) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_EMPTY'); ?></div><?php else : ?>
			<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr>
				<th><?php echo $sortLabel(Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_PERSON'), 'name'); ?></th>
				<?php foreach ($visibleColumns as $column => $visible) : if (!$visible) continue; ?>
					<th class="text-center"><?php echo $sortLabel(Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_' . strtoupper($column)), $column); ?></th>
				<?php endforeach; ?>
			</tr></thead><tbody>
			<?php foreach ($data['managers'] as $manager) :
				$record = $manager['manager_record'];
				$managerUrl = Route::_('index.php?' . http_build_query([
					'option' => 'com_joomleague', 'view' => 'clubstaffhistory', 'club_id' => (int) $data['club']->id,
					'manager_id' => (int) $manager['person_id'],
					'filter_team_id' => (int) ($data['filters']['team_id'] ?? 0),
					'filter_project_id' => (int) $data['filters']['project_id'],
					'filter_season_id' => (int) $data['filters']['season_id'],
					'filter_project_type' => (string) $data['filters']['project_type'],
					'filter_search' => (string) $data['pagination_state']['search'],
					'limit' => (int) $data['pagination_state']['requested_limit'],
					'sort' => $sorting['column'], 'direction' => $sorting['direction'],
				] + $menuQuery));
			?>
				<tr><th scope="row"><a href="<?php echo $managerUrl; ?>"><?php echo htmlspecialchars(trim($manager['last_name'] . ' ' . $manager['first_name']), ENT_QUOTES, 'UTF-8'); ?></a></th>
				<?php foreach ($visibleColumns as $column => $visible) : if (!$visible) continue; ?>
					<td class="text-center">
					<?php if ($column === 'period') : ?>
						<?php foreach (['first', 'last'] as $boundary) :
							$date = $record[$boundary . '_match_date'] ?? null;
							$id = (int) ($record[$boundary . '_match_id'] ?? 0);
						?>
							<div class="text-nowrap">
								<?php if ($date && $id > 0 && $data['show_boundary_matches']) : ?>
									<a href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . $id); ?>" title="<?php echo htmlspecialchars(Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_' . strtoupper($boundary) . '_MATCH'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC3'), $record[$boundary . '_match_timezone'] ?: 'UTC'); ?></a>
								<?php else : ?>
									<?php echo $date ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC3'), $record[$boundary . '_match_timezone'] ?: 'UTC') : '&mdash;'; ?>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					<?php elseif ($column === 'score') : ?>
						<?php echo htmlspecialchars($record['scored'] . ':' . $record['conceded'], ENT_QUOTES, 'UTF-8'); ?>
					<?php else : ?>
						<?php echo (int) $record[$column]; ?>
					<?php endif; ?>
					</td>
				<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
			</tbody></table></div><?php if (($data['pagination']->pagesTotal ?? 1) > 1) : ?><div class="d-flex justify-content-center mt-4"><?php echo $data['pagination']->getPagesLinks(); ?></div><?php endif; ?>
		<?php endif; ?>
		<?php if ($data['selected_manager']) : $selected = $data['selected_manager']; ?>
			<h2 class="h4 mt-5"><?php echo Text::sprintf('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_MATCH_HISTORY', htmlspecialchars(trim($selected['last_name'] . ' ' . $selected['first_name']), ENT_QUOTES, 'UTF-8')); ?></h2>
			<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_DATE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_EVENT'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_PROJECT'); ?></th><th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_CLUBSTAFFHISTORY_RESULT'); ?></th></tr></thead><tbody>
			<?php foreach ($selected['manager_record']['matches'] as $match) : ?><tr><td class="text-nowrap"><?php echo $match['scheduled_start'] ? HTMLHelper::_('date', $match['scheduled_start'], Text::_('DATE_FORMAT_LC3'), $match['timezone']) : ''; ?></td><th scope="row"><a href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $match['match_id']); ?>"><?php echo htmlspecialchars($match['own_team_name'] . ' - ' . $match['opponent_team_name'], ENT_QUOTES, 'UTF-8'); ?></a></th><td><?php echo htmlspecialchars($match['project_name'], ENT_QUOTES, 'UTF-8'); ?><div class="small text-body-secondary"><?php echo htmlspecialchars($match['competition_name'] . ' · ' . $match['season_name'], ENT_QUOTES, 'UTF-8'); ?></div></td><td class="text-center"><span class="badge text-bg-<?php echo $match['outcome'] === 'win' ? 'success' : ($match['outcome'] === 'loss' ? 'danger' : 'secondary'); ?>"><?php echo $match['scored'] !== null && $match['conceded'] !== null ? htmlspecialchars($match['scored'] . ':' . $match['conceded'], ENT_QUOTES, 'UTF-8') : htmlspecialchars(strtoupper($match['outcome']), ENT_QUOTES, 'UTF-8'); ?></span></td></tr><?php endforeach; ?>
			</tbody></table></div>
			<?php if (($data['detail_pagination']->pagesTotal ?? 1) > 1) : ?><div class="d-flex justify-content-center mt-4"><?php echo $data['detail_pagination']->getPagesLinks(); ?></div><?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>
</div>
