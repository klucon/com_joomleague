<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="com-joomleague-site">
	<section class="jl-site-hero mb-4"><div class="jl-site-eyebrow"><?php echo $this->project ? $this->escape($this->project->name) : ''; ?></div><h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAMS'); ?></h1></section>
	<div class="jl-site-grid">
		<?php foreach ($this->teams as $team) : ?>
			<a class="jl-site-card" href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->id); ?>">
				<span><strong><?php echo $this->escape($team->team_name); ?></strong><br><span class="jl-site-muted"><?php echo $this->escape($team->club_name ?? ''); ?></span></span>
				<span class="jl-site-badge"><?php echo $this->escape($team->division_name ?? Text::_('COM_JOOMLEAGUE_SITE_NO_DIVISION')); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php if (!$this->teams) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
</div>
