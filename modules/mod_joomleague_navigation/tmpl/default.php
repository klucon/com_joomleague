<?php

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var array<string,mixed> $navigation */
?>
<?php if (isset($navigation['error'])) : ?>
	<div class="alert alert-info mb-0"><?php echo Text::_((string) $navigation['error']); ?></div>
<?php elseif (($navigation['items'] ?? []) !== []) : ?>
	<nav aria-label="<?php echo Text::_('MOD_JOOMLEAGUE_NAVIGATION_LABEL'); ?>">
		<?php if ((bool) $params->get('show_project_name', 1)) : ?>
			<div class="fw-semibold mb-2"><?php echo htmlspecialchars((string) $navigation['project']->name, ENT_QUOTES, 'UTF-8'); ?></div>
		<?php endif; ?>
		<?php if ((string) $params->get('navigation_style', 'list') === 'pills') : ?>
			<div class="nav nav-pills flex-column gap-1">
				<?php foreach ($navigation['items'] as $item) : ?>
					<a class="nav-link" href="<?php echo $item['url']; ?>"><span class="<?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?> me-2" aria-hidden="true"></span><?php echo Text::_($item['label']); ?></a>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="list-group">
				<?php foreach ($navigation['items'] as $item) : ?>
					<a class="list-group-item list-group-item-action d-flex align-items-center" href="<?php echo $item['url']; ?>"><span class="<?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?> me-2" aria-hidden="true"></span><?php echo Text::_($item['label']); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</nav>
<?php endif; ?>
