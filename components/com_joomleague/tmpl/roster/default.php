<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
$team = $this->item;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';
$params = $this->templateParams;
$show = static fn (string $name, bool $default = true): bool => array_key_exists($name, $params) ? (bool) $params[$name] : $default;
$layout = ($params['layout'] ?? 'table') === 'cards' ? 'cards' : 'table';
?>
<div class="com-joomleague-site">
	<?php if (!$team) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_NOT_FOUND'); ?></div><?php return; endif; ?>
	<section class="jl-site-hero mb-4"><div class="jl-site-eyebrow"><?php echo $this->escape($team->team_name); ?></div><h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROSTER'); ?></h1></section>
	<?php if ($layout === 'cards') : ?>
		<div class="jl-site-grid">
			<?php foreach ($this->items as $player) : ?>
				<article class="jl-site-card">
					<strong><?php if ($show('show_jersey_number') && $player->jerseynumber !== null) : ?>#<?php echo (int) $player->jerseynumber; ?> · <?php endif; ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $player->person_id . '&project_id=' . (int) $team->project_id); ?>"><?php echo $this->escape($player->person_name); ?></a></strong>
					<?php if ($show('show_position') && !empty($player->position_name)) : ?><span class="jl-site-muted"><?php echo $this->escape(Text::_($player->position_name)); ?></span><?php endif; ?>
					<?php if ($show('show_country_flag')) : ?><?php echo LayoutHelper::render('joomleague.flag', ['code' => $player->person_country ?? '', 'showName' => false, 'size' => 18], $jlFlagPath); ?><?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
	<div class="jl-site-panel table-responsive">
		<table class="table jl-site-table align-middle">
			<thead><tr><?php if ($show('show_jersey_number')) : ?><th>#</th><?php endif; ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></th><?php if ($show('show_position')) : ?><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_POSITION'); ?></th><?php endif; ?></tr></thead>
			<tbody><?php foreach ($this->items as $player) : ?><tr><?php if ($show('show_jersey_number')) : ?><td><?php echo $this->escape((string) ($player->jerseynumber ?? '')); ?></td><?php endif; ?><td><?php if ($show('show_country_flag')) : ?><?php echo LayoutHelper::render('joomleague.flag', ['code' => $player->person_country ?? '', 'showName' => false, 'size' => 18], $jlFlagPath); ?><?php endif; ?> <a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $player->person_id . '&project_id=' . (int) $team->project_id); ?>"><?php echo $this->escape($player->person_name); ?></a></td><?php if ($show('show_position')) : ?><td><?php echo $this->escape(Text::_($player->position_name ?? '')); ?></td><?php endif; ?></tr><?php endforeach; ?></tbody>
		</table>
	</div>
	<?php endif; ?>
	<?php if (!$this->items) : ?><div class="alert alert-info mt-3"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
</div>
