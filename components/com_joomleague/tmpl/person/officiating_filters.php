<?php
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$filters = $officiating['filters'] ?? [];
?>
<form class="row g-2 align-items-end mb-3" method="get" action="<?php echo Route::_('index.php'); ?>">
	<input type="hidden" name="option" value="com_joomleague">
	<input type="hidden" name="view" value="person">
	<input type="hidden" name="person_id" value="<?php echo (int) $person->id; ?>">
	<?php if (($itemId = \Joomla\CMS\Factory::getApplication()->getInput()->getInt('Itemid')) > 0) : ?><input type="hidden" name="Itemid" value="<?php echo $itemId; ?>"><?php endif; ?>
	<div class="col-12 col-md-6 col-xl-3">
		<label class="form-label" for="referee-filter-team"><?php echo Text::_('COM_JOOMLEAGUE_TEAM_REQUEST_TEAM_LABEL'); ?></label>
		<select class="form-select" id="referee-filter-team" name="filter_team_id">
			<option value="0"><?php echo Text::_((int) ($filters['site_club_id'] ?? 0) > 0 ? 'COM_JOOMLEAGUE_CLUBRIVALS_ALL_TEAMS' : 'JALL'); ?></option>
			<?php foreach (($filters['teams'] ?? []) as $id=>$label) : ?><option value="<?php echo (int) $id; ?>"<?php echo (int) ($filters['team_id'] ?? 0) === (int) $id ? ' selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
		</select>
	</div>
	<div class="col-12 col-md-6 col-xl-3">
		<label class="form-label" for="referee-filter-project"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_FILTER_PROJECT'); ?></label>
		<select class="form-select" id="referee-filter-project" name="filter_project_id">
			<option value="0"><?php echo Text::_('JALL'); ?></option>
			<?php foreach (($filters['projects'] ?? []) as $id=>$label) : ?><option value="<?php echo (int) $id; ?>"<?php echo (int) ($filters['project_id'] ?? 0) === (int) $id ? ' selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
		</select>
	</div>
	<div class="col-12 col-md-6 col-xl-3">
		<label class="form-label" for="referee-filter-role"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_FILTER_ROLE'); ?></label>
		<select class="form-select" id="referee-filter-role" name="filter_role_code">
			<option value=""><?php echo Text::_('JALL'); ?></option>
			<?php foreach (($filters['roles'] ?? []) as $code=>$role) : $label = $role['name_key'] !== '' ? Text::_($role['name_key']) : ($role['name'] !== '' ? $role['name'] : $code); ?><option value="<?php echo htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (string) ($filters['role_code'] ?? '') === (string) $code ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
		</select>
	</div>
	<div class="col-12 col-md-6 col-xl-3">
		<label class="form-label" for="referee-filter-opponent"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_FILTER_OPPONENT'); ?></label>
		<select class="form-select" id="referee-filter-opponent" name="filter_opponent_club_id">
			<option value="0"><?php echo Text::_('JALL'); ?></option>
			<?php foreach (($filters['opponents'] ?? []) as $id=>$label) : ?><option value="<?php echo (int) $id; ?>"<?php echo (int) ($filters['opponent_club_id'] ?? 0) === (int) $id ? ' selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
		</select>
	</div>
	<div class="col-12 d-flex gap-2">
		<button class="btn btn-primary" type="submit"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_FILTER_APPLY'); ?></button>
		<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $person->id . ($itemId > 0 ? '&Itemid=' . $itemId : '')); ?>" title="<?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_FILTER_RESET'); ?>"><span class="icon-refresh" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_OFFICIATED_FILTER_RESET'); ?></span></a>
	</div>
</form>
