<?php

declare(strict_types=1);

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Clubrivals\HtmlView $this */
$data = $this->rivals;
$projectTypeLabel = static fn (string $code): string => Text::_('COM_JOOMLEAGUE_PROJECT_TYPE_' . strtoupper(str_replace('-', '_', $code)));
$paginationState = $data['pagination_state'] ?? ['total' => 0, 'start' => 0, 'limit' => 25, 'requested_limit' => 25, 'search' => '', 'all_capped' => false];
$rangeFrom = $paginationState['total'] > 0 ? $paginationState['start'] + 1 : 0;
$rangeTo = min($paginationState['total'], $paginationState['start'] + $paginationState['limit']);
$filterQuery = http_build_query(array_filter([
	'filter_project_id' => (int) ($data['filters']['project_id'] ?? 0),
	'filter_season_id' => (int) ($data['filters']['season_id'] ?? 0),
	'filter_project_type' => (string) ($data['filters']['project_type'] ?? ''),
], static fn ($value): bool => $value !== 0 && $value !== ''));
$sorting = $data['sorting'] ?? ['enabled' => false, 'column' => 'opponent', 'direction' => 'asc'];
$sortUrl = static function (string $column) use ($data, $paginationState, $sorting): string {
	$direction = $sorting['column'] === $column && $sorting['direction'] === 'asc' ? 'desc' : 'asc';
	return Route::_('index.php?' . http_build_query([
		'option' => 'com_joomleague', 'view' => 'clubrivals', 'club_id' => (int) $data['club']->id,
		'filter_project_id' => (int) $data['filters']['project_id'], 'filter_season_id' => (int) $data['filters']['season_id'],
		'filter_project_type' => (string) $data['filters']['project_type'], 'filter_opponent_club_id' => (int) $data['filters']['opponent_club_id'],
		'filter_search' => (string) $paginationState['search'], 'limit' => (int) $paginationState['requested_limit'],
		'sort' => $column, 'direction' => $direction,
	]));
};
$sortLabel = static function (string $label, string $column) use ($sorting, $sortUrl): string {
	if (!$sorting['enabled']) return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
	$active = $sorting['column'] === $column;
	$icon = $active ? ($sorting['direction'] === 'asc' ? 'icon-arrow-up' : 'icon-arrow-down') : 'icon-sort';
	return '<a class="text-decoration-none text-body d-inline-flex align-items-center gap-1" href="' . $sortUrl($column) . '"><span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span><span class="' . $icon . '" aria-hidden="true"></span></a>';
};
?>
<div class="com-joomleague-clubrivals">
	<?php if (isset($data['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
	<?php else : ?>
		<header class="border-bottom pb-3 mb-4 d-flex flex-wrap gap-3 align-items-center">
			<?php if ($data['club']->logo) : ?><img src="<?php echo htmlspecialchars((string) $data['club']->logo, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="72" height="72" class="img-fluid"><?php endif; ?>
			<div><h1 class="mb-1"><?php echo Text::sprintf('COM_JOOMLEAGUE_CLUBRIVALS_PAGE_TITLE', htmlspecialchars((string) $data['club']->name, ENT_QUOTES, 'UTF-8')); ?></h1><p class="text-body-secondary mb-0"><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_PAGE_DESC'); ?></p></div>
		</header>

		<form class="row g-3 align-items-end mb-4" method="get" action="<?php echo Route::_('index.php'); ?>">
			<input type="hidden" name="option" value="com_joomleague"><input type="hidden" name="view" value="clubrivals"><input type="hidden" name="club_id" value="<?php echo (int) $data['club']->id; ?>"><input type="hidden" name="start" value="0"><input type="hidden" name="sort" value="<?php echo htmlspecialchars($sorting['column'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="direction" value="<?php echo htmlspecialchars($sorting['direction'], ENT_QUOTES, 'UTF-8'); ?>">
			<div class="col-12 col-md-4"><label class="form-label" for="clubrivals-project"><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_FILTER_PROJECT'); ?></label><select class="form-select" id="clubrivals-project" name="filter_project_id"><option value="0"><?php echo Text::_('JALL'); ?></option><?php foreach ($data['options']['projects'] as $id => $name) : ?><option value="<?php echo (int) $id; ?>"<?php echo (int) $data['filters']['project_id'] === (int) $id ? ' selected' : ''; ?>><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
			<div class="col-12 col-md-3"><label class="form-label" for="clubrivals-season"><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_FILTER_SEASON'); ?></label><select class="form-select" id="clubrivals-season" name="filter_season_id"><option value="0"><?php echo Text::_('JALL'); ?></option><?php foreach ($data['options']['seasons'] as $id => $name) : ?><option value="<?php echo (int) $id; ?>"<?php echo (int) $data['filters']['season_id'] === (int) $id ? ' selected' : ''; ?>><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
			<div class="col-12 col-md-3"><label class="form-label" for="clubrivals-type"><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_FILTER_PROJECT_TYPE'); ?></label><select class="form-select" id="clubrivals-type" name="filter_project_type"><option value=""><?php echo Text::_('JALL'); ?></option><?php foreach ($data['options']['project_types'] as $value => $name) : ?><option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $data['filters']['project_type'] === $value ? ' selected' : ''; ?>><?php echo htmlspecialchars($projectTypeLabel($value), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
			<div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_FILTER_APPLY'); ?></button><a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=clubrivals&club_id=' . (int) $data['club']->id); ?>" title="<?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_FILTER_RESET'); ?>"><span class="icon-refresh" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_FILTER_RESET'); ?></span></a></div>
			<div class="col-12 col-md-6"><label class="form-label" for="clubrivals-search"><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_FILTER_SEARCH'); ?></label><input class="form-control" id="clubrivals-search" name="filter_search" type="search" value="<?php echo htmlspecialchars((string) $paginationState['search'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_FILTER_SEARCH_HINT'); ?>"></div>
			<div class="col-7 col-md-3"><label class="form-label" for="clubrivals-limit"><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_PAGE_SIZE'); ?></label><select class="form-select" id="clubrivals-limit" name="limit" onchange="this.form.submit()"><?php foreach ([10, 25, 50, 100, 0] as $size) : ?><option value="<?php echo $size; ?>"<?php echo (int) $paginationState['requested_limit'] === $size ? ' selected' : ''; ?>><?php echo $size === 0 ? Text::_('JALL') : $size; ?></option><?php endforeach; ?></select></div>
			<div class="col-5 col-md-3 text-end text-body-secondary pb-2" aria-live="polite"><?php echo Text::sprintf('COM_JOOMLEAGUE_CLUBRIVALS_RANGE', $rangeFrom, $rangeTo, (int) $paginationState['total']); ?></div>
		</form>
		<?php if ($paginationState['all_capped']) : ?><div class="alert alert-info"><?php echo Text::sprintf('COM_JOOMLEAGUE_CLUBRIVALS_ALL_LIMIT', 1000); ?></div><?php endif; ?>

		<?php if ($data['opponents'] === []) : ?>
			<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_EMPTY'); ?></div>
		<?php else : ?>
			<div class="table-responsive"><table class="table table-striped align-middle">
				<thead><tr><th><?php echo $sortLabel(Text::_('COM_JOOMLEAGUE_CLUBRIVALS_OPPONENT'), 'opponent'); ?></th><th class="text-center"><?php echo $sortLabel(Text::_('COM_JOOMLEAGUE_CLUBRIVALS_PLAYED'), 'played'); ?></th><th class="text-center"><?php echo $sortLabel(Text::_('COM_JOOMLEAGUE_CLUBRIVALS_WINS'), 'wins'); ?></th><th class="text-center"><?php echo $sortLabel(Text::_('COM_JOOMLEAGUE_CLUBRIVALS_DRAWS'), 'draws'); ?></th><th class="text-center"><?php echo $sortLabel(Text::_('COM_JOOMLEAGUE_CLUBRIVALS_LOSSES'), 'losses'); ?></th><th class="text-center"><?php echo $sortLabel(Text::_('COM_JOOMLEAGUE_CLUBRIVALS_SCORE'), 'score'); ?></th><th class="text-nowrap"><?php echo $sortLabel(Text::_('COM_JOOMLEAGUE_CLUBRIVALS_PERIOD'), 'period'); ?></th></tr></thead>
				<tbody><?php foreach ($data['opponents'] as $rival) : ?><tr><th scope="row"><a href="<?php echo Route::_('index.php?option=com_joomleague&view=clubrivals&club_id=' . (int) $data['club']->id . '&filter_opponent_club_id=' . (int) $rival['club_id'] . ($filterQuery !== '' ? '&' . $filterQuery : '')); ?>"><?php echo htmlspecialchars($rival['club_name'], ENT_QUOTES, 'UTF-8'); ?></a></th><td class="text-center"><?php echo (int) $rival['played']; ?></td><td class="text-center text-success"><?php echo (int) $rival['wins']; ?></td><td class="text-center"><?php echo (int) $rival['draws']; ?></td><td class="text-center text-danger"><?php echo (int) $rival['losses']; ?></td><td class="text-center fw-semibold"><?php echo htmlspecialchars($rival['scored'] . ':' . $rival['conceded'], ENT_QUOTES, 'UTF-8'); ?></td><td class="text-nowrap"><?php echo $rival['first_match'] ? HTMLHelper::_('date', $rival['first_match'], Text::_('DATE_FORMAT_LC3')) : ''; ?><?php if ($rival['last_match'] && $rival['last_match'] !== $rival['first_match']) : ?> &ndash; <?php echo HTMLHelper::_('date', $rival['last_match'], Text::_('DATE_FORMAT_LC3')); ?><?php endif; ?></td></tr><?php endforeach; ?></tbody>
			</table></div>
			<?php if (($data['pagination']->pagesTotal ?? 1) > 1) : ?><div class="d-flex justify-content-center mt-4"><?php echo $data['pagination']->getPagesLinks(); ?></div><?php endif; ?>
		<?php endif; ?>

		<?php if ((int) $data['filters']['opponent_club_id'] > 0 && $data['matches'] !== []) : ?>
			<h2 class="h4 mt-5"><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_MATCH_HISTORY'); ?></h2>
			<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_DATE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_EVENT'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_PROJECT'); ?></th><th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_RESULT'); ?></th></tr></thead><tbody>
			<?php foreach ($data['matches'] as $match) : ?><tr><td class="text-nowrap"><?php echo $match['scheduled_start'] ? HTMLHelper::_('date', $match['scheduled_start'], Text::_('DATE_FORMAT_LC3'), $match['timezone']) : Text::_('COM_JOOMLEAGUE_CLUBRIVALS_DATE_UNKNOWN'); ?></td><th scope="row"><a href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $match['match_id']); ?>"><?php echo htmlspecialchars($match['own_team_name'] . ' - ' . $match['opponent_team_name'], ENT_QUOTES, 'UTF-8'); ?></a><div class="small fw-normal text-body-secondary"><?php echo htmlspecialchars($match['match_label'], ENT_QUOTES, 'UTF-8'); ?></div></th><td><?php echo htmlspecialchars($match['project_name'], ENT_QUOTES, 'UTF-8'); ?><div class="small text-body-secondary"><?php echo htmlspecialchars($match['competition_name'] . ' · ' . $match['season_name'], ENT_QUOTES, 'UTF-8'); ?></div></td><td class="text-center"><span class="badge text-bg-<?php echo $match['outcome'] === 'win' ? 'success' : ($match['outcome'] === 'loss' ? 'danger' : 'secondary'); ?>"><?php echo htmlspecialchars($match['scored'] . ':' . $match['conceded'], ENT_QUOTES, 'UTF-8'); ?></span></td></tr><?php endforeach; ?>
			</tbody></table></div>
		<?php endif; ?>
	<?php endif; ?>
</div>
