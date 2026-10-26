<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$data = $this->people;
$menuQuery = !empty($data['menu_id']) ? ['Itemid' => (int) $data['menu_id']] : [];
$labelCode = static function (string $prefix, string $code): string {
	$key = $prefix . strtoupper(str_replace('-', '_', $code));
	$translated = Text::_($key);
	return $translated === $key ? ucwords(str_replace('_', ' ', $code)) : $translated;
};
$projectTypeLabel = static fn (string $code): string => $labelCode('COM_JOOMLEAGUE_PROJECT_TYPE_', $code);
$personTypeLabel = static fn (string $code): string => $labelCode('COM_JOOMLEAGUE_PERSON_TYPE_', $code);
$metricLabel = static function (string $code, string $key) use ($labelCode): string {
	if ($key !== '') {
		$translated = Text::_($key);
		if ($translated !== $key) return $translated;
	}
	return $labelCode('COM_JOOMLEAGUE_STATISTIC_', $code);
};
$sorting = $data['sorting'] ?? ['enabled' => false, 'column' => 'name', 'direction' => 'asc'];
$sortUrl = static function (string $column) use ($data, $sorting, $menuQuery): string {
	$direction = $sorting['column'] === $column && $sorting['direction'] === 'asc' ? 'desc' : 'asc';
	return Route::_('index.php?' . http_build_query([
		'option' => 'com_joomleague', 'view' => 'clubpeople', 'club_id' => (int) $data['club']->id,
		'filter_team_id' => (int) ($data['filters']['team_id'] ?? 0), 'filter_project_id' => (int) $data['filters']['project_id'], 'filter_season_id' => (int) $data['filters']['season_id'],
		'filter_project_type' => (string) $data['filters']['project_type'], 'filter_person_type' => (string) $data['filters']['person_type'],
		'filter_search' => (string) $data['pagination_state']['search'], 'limit' => (int) $data['pagination_state']['requested_limit'],
		'sort' => $column, 'direction' => $direction,
	] + $menuQuery));
};
$sortLabel = static function (string $label, string $column) use ($sorting, $sortUrl): string {
	if (!$sorting['enabled']) return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
	$active = $sorting['column'] === $column;
	$icon = $active ? ($sorting['direction'] === 'asc' ? 'icon-arrow-up' : 'icon-arrow-down') : 'icon-sort';
	return '<a class="text-decoration-none text-body d-inline-flex align-items-center gap-1" href="' . $sortUrl($column) . '"><span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span><span class="' . $icon . '" aria-hidden="true"></span></a>';
};
?>
<div class="com-joomleague-clubpeople">
	<?php if (isset($data['error'])) : ?>
		<div class="alert alert-info"><?php echo Text::_($data['error']); ?></div>
	<?php else : ?>
		<h1><?php echo Text::sprintf('COM_JOOMLEAGUE_CLUBPEOPLE_PAGE_TITLE', htmlspecialchars($data['club']->name, ENT_QUOTES, 'UTF-8')); ?></h1>
		<p class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_PAGE_DESC'); ?></p>

		<form method="get" action="<?php echo Route::_('index.php'); ?>" class="row g-3 align-items-end mb-4">
			<?php if ($menuQuery !== []) : ?><input type="hidden" name="Itemid" value="<?php echo (int) $data['menu_id']; ?>"><?php endif; ?>
			<input type="hidden" name="option" value="com_joomleague"><input type="hidden" name="view" value="clubpeople"><input type="hidden" name="club_id" value="<?php echo (int) $data['club']->id; ?>"><input type="hidden" name="start" value="0"><input type="hidden" name="sort" value="<?php echo htmlspecialchars($sorting['column'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="direction" value="<?php echo htmlspecialchars($sorting['direction'], ENT_QUOTES, 'UTF-8'); ?>">
			<?php echo LayoutHelper::render('history.teamfilter', ['data' => $data, 'id' => 'clubpeople-team', 'all_label' => Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_ALL_TEAMS')], JPATH_COMPONENT . '/layouts'); ?>
			<div class="col-12 col-md-3"><label class="form-label" for="clubpeople-project"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_FILTER_PROJECT'); ?></label><select class="form-select" id="clubpeople-project" name="filter_project_id"><option value="0"><?php echo Text::_('JALL'); ?></option><?php foreach ($data['options']['projects'] as $id => $name) : ?><option value="<?php echo (int) $id; ?>"<?php echo (int) $data['filters']['project_id'] === (int) $id ? ' selected' : ''; ?>><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
			<div class="col-12 col-md-3"><label class="form-label" for="clubpeople-season"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_FILTER_SEASON'); ?></label><select class="form-select" id="clubpeople-season" name="filter_season_id"><option value="0"><?php echo Text::_('JALL'); ?></option><?php foreach ($data['options']['seasons'] as $id => $name) : ?><option value="<?php echo (int) $id; ?>"<?php echo (int) $data['filters']['season_id'] === (int) $id ? ' selected' : ''; ?>><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
			<div class="col-12 col-md-3"><label class="form-label" for="clubpeople-type"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_FILTER_PROJECT_TYPE'); ?></label><select class="form-select" id="clubpeople-type" name="filter_project_type"><option value=""><?php echo Text::_('JALL'); ?></option><?php foreach ($data['options']['project_types'] as $value => $name) : ?><option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $data['filters']['project_type'] === $value ? ' selected' : ''; ?>><?php echo htmlspecialchars($projectTypeLabel($value), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
			<div class="col-12 col-md-3"><label class="form-label" for="clubpeople-person-type"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_FILTER_PERSON_TYPE'); ?></label><select class="form-select" id="clubpeople-person-type" name="filter_person_type"><option value=""><?php echo Text::_('JALL'); ?></option><?php foreach ($data['options']['person_types'] as $value => $name) : ?><option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $data['filters']['person_type'] === $value ? ' selected' : ''; ?>><?php echo htmlspecialchars($personTypeLabel($value), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
			<div class="col-12 col-md-6"><label class="form-label" for="clubpeople-search"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_FILTER_SEARCH'); ?></label><input class="form-control" id="clubpeople-search" name="filter_search" type="search" value="<?php echo htmlspecialchars($data['pagination_state']['search'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_FILTER_SEARCH_HINT'); ?>"></div>
			<div class="col-7 col-md-3"><label class="form-label" for="clubpeople-limit"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_PAGE_SIZE'); ?></label><select class="form-select" id="clubpeople-limit" name="limit" onchange="this.form.submit()"><?php foreach ([10, 25, 50, 100, 0] as $size) : ?><option value="<?php echo $size; ?>"<?php echo (int) $data['pagination_state']['requested_limit'] === $size ? ' selected' : ''; ?>><?php echo $size === 0 ? Text::_('JALL') : $size; ?></option><?php endforeach; ?></select></div>
			<div class="col-5 col-md-3 d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_FILTER_APPLY'); ?></button><a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?' . http_build_query(['option' => 'com_joomleague', 'view' => 'clubpeople', 'club_id' => (int) $data['club']->id] + $menuQuery)); ?>" title="<?php echo htmlspecialchars(Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_FILTER_RESET'), ENT_QUOTES, 'UTF-8'); ?>"><span class="icon-refresh" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_FILTER_RESET'); ?></span></a></div>
		</form>

		<?php if ($data['people'] === []) : ?>
			<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_EMPTY'); ?></div>
		<?php else : ?>
			<p class="small text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_CLUBPEOPLE_RANGE', $data['pagination_state']['start'] + 1, min($data['pagination_state']['start'] + $data['pagination_state']['limit'], $data['pagination_state']['total']), $data['pagination_state']['total']); ?></p>
			<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th><?php echo $sortLabel(Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_PERSON'), 'name'); ?></th><th><?php echo $sortLabel(Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_TYPE'), 'type'); ?></th><th class="text-center"><?php echo $sortLabel(Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_APPEARANCES'), 'appearances'); ?></th><?php foreach ($data['metric_labels'] as $code => $key) : ?><th class="text-center"><?php echo $sortLabel($metricLabel($code, $key), 'metric_' . $code); ?></th><?php endforeach; ?><?php if ($data['has_manager_records']) : ?><th><?php echo $sortLabel(Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_MANAGER_RECORD'), 'manager_record'); ?></th><?php endif; ?><th><?php echo $sortLabel(Text::_('COM_JOOMLEAGUE_CLUBPEOPLE_PERIOD'), 'period'); ?></th></tr></thead><tbody>
			<?php foreach ($data['people'] as $person) : ?><tr><th scope="row"><a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $person['person_id']); ?>"><?php echo htmlspecialchars(trim($person['last_name'] . ' ' . $person['first_name']), ENT_QUOTES, 'UTF-8'); ?></a><?php if ($person['nickname'] !== '') : ?><div class="small fw-normal text-body-secondary"><?php echo htmlspecialchars($person['nickname'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?></th><td><?php echo htmlspecialchars(implode(', ', array_map($personTypeLabel, $person['person_types'])), ENT_QUOTES, 'UTF-8'); ?></td><td class="text-center fw-semibold"><?php echo (int) $person['appearances']; ?></td><?php foreach ($data['metric_labels'] as $code => $unused) : ?><td class="text-center"><?php echo (int) ($person['metrics'][$code] ?? 0); ?></td><?php endforeach; ?><?php if ($data['has_manager_records']) : ?><td class="text-nowrap"><?php if ($person['manager_record']) : ?><?php echo Text::sprintf('COM_JOOMLEAGUE_CLUBPEOPLE_MANAGER_RECORD_VALUE', $person['manager_record']['played'], $person['manager_record']['wins'], $person['manager_record']['draws'], $person['manager_record']['losses']); ?><?php else : ?>—<?php endif; ?></td><?php endif; ?><td class="text-nowrap"><?php if ($person['first_date']) : ?><?php echo HTMLHelper::_('date', $person['first_date'], 'Y'); ?><?php if ($person['last_date'] && substr($person['last_date'], 0, 4) !== substr($person['first_date'], 0, 4)) : ?>–<?php echo HTMLHelper::_('date', $person['last_date'], 'Y'); ?><?php endif; ?><?php else : ?>—<?php endif; ?></td></tr><?php endforeach; ?>
			</tbody></table></div>
			<?php if (($data['pagination']->pagesTotal ?? 1) > 1) : ?><div class="d-flex justify-content-center mt-4"><?php echo $data['pagination']->getPagesLinks(); ?></div><?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>
</div>
