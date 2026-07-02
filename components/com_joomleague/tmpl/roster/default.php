<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
$team = $this->item;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';
?>
<div class="com-joomleague-site">
	<?php if (!$team) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_NOT_FOUND'); ?></div><?php return; endif; ?>
	<section class="jl-site-hero mb-4"><div class="jl-site-eyebrow"><?php echo $this->escape($team->team_name); ?></div><h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROSTER'); ?></h1></section>
	<div class="jl-site-panel table-responsive">
		<table class="table jl-site-table align-middle">
			<thead><tr><th>#</th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_POSITION'); ?></th></tr></thead>
			<tbody><?php foreach ($this->items as $player) : ?><tr><td><?php echo $this->escape((string) ($player->jerseynumber ?? '')); ?></td><td><?php echo LayoutHelper::render('joomleague.flag', ['code' => $player->person_country ?? '', 'showName' => false, 'size' => 18], $jlFlagPath); ?> <a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $player->person_id . '&project_id=' . (int) $team->project_id); ?>"><?php echo $this->escape($player->person_name); ?></a></td><td><?php echo $this->escape($player->position_name ?? ''); ?></td></tr><?php endforeach; ?></tbody>
		</table>
		<?php if (!$this->items) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
	</div>
</div>
