<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$review = $this->profileMigration;
$checked = Factory::getApplication()->getInput()->getBool('profile_check');
$asset = 'com_joomleague.project.' . (int) $this->item->id;
$user = Factory::getApplication()->getIdentity();
$canMigrate = $user->authorise('core.edit', $asset) && $user->authorise('joomleague.project.edit.rules', $asset);
?>
<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_INTRO'); ?></div>
<?php if ($review === null) : ?>
	<p><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_SAVE_FIRST'); ?></p>
<?php else : ?>
	<dl class="row">
		<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PROFILE'); ?></dt><dd class="col-sm-8"><?php echo $this->escape(Text::_($review['current']['name_key'])); ?></dd>
		<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_CURRENT'); ?></dt><dd class="col-sm-8"><?php echo $this->escape($review['current']['profile_version']); ?></dd>
		<dt class="col-sm-4"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SCHEMA_VERSION'); ?></dt><dd class="col-sm-8"><?php echo $this->escape($review['current']['schema_version']); ?></dd>
	</dl>
	<a class="btn btn-outline-primary mb-3" href="<?php echo Route::_('index.php?option=com_joomleague&view=project&layout=edit&id=' . (int) $this->item->id . '&profile_check=1'); ?>"><span class="icon-refresh me-2" aria-hidden="true"></span><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_CHECK'); ?></a>
	<?php if (isset($review['variant'])) : ?>
		<div class="alert alert-info"><?php echo Text::sprintf('COM_JOOMLEAGUE_PROJECT_VARIANT_PINNED', $this->escape($review['variant']['revision'])); ?> <?php echo Text::_('COM_JOOMLEAGUE_PROJECT_VARIANT_LOCKED'); ?></div>
		<?php if ($review['variant_target'] !== null) : ?>
			<div class="alert alert-warning"><?php echo Text::sprintf('COM_JOOMLEAGUE_VARIANT_REVIEW_AVAILABLE', $this->escape($review['variant_target']['revision'])); ?></div>
			<?php if ($checked) : ?>
				<h3><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_CHANGES'); ?></h3>
				<?php if ($review['changes'] === []) : ?><p><?php echo Text::_('COM_JOOMLEAGUE_VARIANT_REVIEW_EQUIVALENT'); ?></p><?php endif; ?>
				<div class="table-responsive"><table class="table table-striped align-middle">
					<thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_PATH'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_OLD'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_NEW'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_COMPATIBILITY'); ?></th></tr></thead>
					<tbody><?php foreach ($review['changes'] as $change) : ?><tr>
						<td><code class="text-break"><?php echo $this->escape($change['path']); ?></code></td>
						<td class="text-break"><?php echo $this->escape(json_encode($change['from'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></td>
						<td class="text-break"><?php echo $this->escape(json_encode($change['to'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></td>
						<td><?php echo Text::_($change['compatible'] ? 'COM_JOOMLEAGUE_PROFILE_MIGRATION_COMPATIBLE' : 'COM_JOOMLEAGUE_PROFILE_MIGRATION_DOMAIN_REQUIRED'); ?></td>
					</tr><?php endforeach; ?></tbody>
				</table></div>
				<?php if ($review['blockers'] !== []) : ?><div class="alert alert-danger"><p><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_BLOCKED'); ?></p><ul><?php foreach ($review['blockers'] as $blocker) : ?><li class="text-break"><?php echo $this->escape($blocker); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
				<dl class="row"><?php foreach ($review['impact'] as $kind => $count) : ?><dt class="col-sm-6"><?php echo Text::_('COM_JOOMLEAGUE_VARIANT_IMPACT_' . strtoupper($kind)); ?></dt><dd class="col-sm-6"><?php echo (int) $count; ?></dd><?php endforeach; ?></dl>
				<?php if ($review['blockers'] === [] && $canMigrate) : ?>
					<div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="profile-acknowledge" name="acknowledge" value="1" required form="profile-migration-form"><label class="form-check-label" for="profile-acknowledge"><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_ACKNOWLEDGE'); ?></label></div>
					<button class="btn btn-primary" type="submit" form="profile-migration-form"><span class="icon-refresh me-2" aria-hidden="true"></span><?php echo Text::sprintf('COM_JOOMLEAGUE_PROFILE_MIGRATION_APPLY', $this->escape($review['variant_target']['revision'])); ?></button>
				<?php else : ?><p><?php echo Text::_('COM_JOOMLEAGUE_VARIANT_REVIEW_ONLY'); ?></p><?php endif; ?>
			<?php endif; ?>
		<?php else : ?><p><?php echo Text::_('COM_JOOMLEAGUE_VARIANT_REVIEW_CURRENT'); ?></p><?php endif; ?>
	<?php elseif ($review['target'] === null) : ?>
		<div class="alert alert-success"><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_CURRENT_STATUS'); ?></div>
	<?php else : ?>
		<div class="alert alert-warning"><?php echo Text::sprintf('COM_JOOMLEAGUE_PROFILE_MIGRATION_AVAILABLE', $this->escape($review['target']['profile_version'])); ?></div>
		<?php if ($checked) : ?>
			<h3><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_CHANGES'); ?></h3>
			<div class="table-responsive"><table class="table table-striped align-middle">
				<thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_PATH'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_OLD'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_NEW'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_COMPATIBILITY'); ?></th></tr></thead>
				<tbody><?php foreach ($review['changes'] as $change) : ?><tr>
					<td><code class="text-break"><?php echo $this->escape($change['path']); ?></code></td>
					<td class="text-break"><?php echo $this->escape(json_encode($change['from'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></td>
					<td class="text-break"><?php echo $this->escape(json_encode($change['to'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></td>
					<td><?php echo Text::_($change['compatible'] ? 'COM_JOOMLEAGUE_PROFILE_MIGRATION_COMPATIBLE' : 'COM_JOOMLEAGUE_PROFILE_MIGRATION_DOMAIN_REQUIRED'); ?></td>
				</tr><?php endforeach; ?></tbody>
			</table></div>
			<?php if ($review['blockers'] !== []) : ?>
				<div class="alert alert-danger"><p><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_BLOCKED'); ?></p><ul><?php foreach ($review['blockers'] as $blocker) : ?><li class="text-break"><?php echo $this->escape($blocker); ?></li><?php endforeach; ?></ul></div>
			<?php elseif ($canMigrate) : ?>
				<div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="profile-acknowledge" name="acknowledge" value="1" required form="profile-migration-form"><label class="form-check-label" for="profile-acknowledge"><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_ACKNOWLEDGE'); ?></label></div>
				<button class="btn btn-primary" type="submit" form="profile-migration-form"><span class="icon-refresh me-2" aria-hidden="true"></span><?php echo Text::sprintf('COM_JOOMLEAGUE_PROFILE_MIGRATION_APPLY', $this->escape($review['target']['profile_version'])); ?></button>
			<?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>
	<h3 class="mt-4"><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_HISTORY'); ?></h3>
	<?php if ($review['history'] === []) : ?><p><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_NO_HISTORY'); ?></p>
	<?php else : ?><div class="table-responsive"><table class="table table-striped"><thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_OLD'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_NEW'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_DATE'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_ACTOR'); ?></th></tr></thead><tbody>
		<?php foreach ($review['history'] as $row) : ?><tr><td><?php echo $this->escape($row['from_version']); ?></td><td><?php echo $this->escape($row['to_version']); ?></td><td><?php echo $this->escape($row['created']); ?></td><td><?php echo $this->escape($row['actor_name'] ?? (string) $row['created_by']); ?></td></tr><?php endforeach; ?>
	</tbody></table></div><?php endif; ?>
<?php endif; ?>
