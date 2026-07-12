<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="com-joomleague-site">
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo Text::_('COM_JOOMLEAGUE_SITE_COMPETITIONS'); ?></div>
		<h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECTS_TITLE'); ?></h1>
		<p class="jl-site-muted mb-0"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECTS_DESC'); ?></p>
	</section>
	<div class="jl-site-grid">
		<?php foreach ($this->items as $item) : ?>
			<a class="jl-site-card" href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $item->id); ?>">
				<span>
					<strong><?php echo $this->escape($item->name); ?></strong><br>
					<span class="jl-site-muted"><?php echo $this->escape(trim(($item->league_name ?? '') . ' · ' . ($item->season_name ?? ''), ' ·')); ?></span>
				</span>
				<span class="d-flex gap-2 mt-3">
					<span class="jl-site-badge"><?php echo (int) $item->rounds . ' ' . Text::_('COM_JOOMLEAGUE_SITE_ROUNDS'); ?></span>
					<span class="jl-site-badge"><?php echo (int) $item->matches . ' ' . Text::_('COM_JOOMLEAGUE_SITE_MATCHES'); ?></span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php if (!$this->items) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
</div>
