<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$showSectionheader = (bool) ($this->templateParams['show_sectionheader'] ?? true);

StructuredDataHelper::add($this->getDocument(), [
	'@context' => 'https://schema.org',
	'@type' => 'Dataset',
	'@id' => StructuredDataHelper::currentUrl() . '#dataset',
	'name' => Text::_('COM_JOOMLEAGUE_SITE_STATS'),
	'description' => $this->projectLabel(),
	'mainEntityOfPage' => StructuredDataHelper::collectionPage(
		Text::_('COM_JOOMLEAGUE_SITE_STATS'),
		array_map(
			static fn (object $item): array => [
				'@type' => 'Observation',
				'name' => (string) $item->statistic_name,
				'value' => (string) $item->value,
				'about' => array_values(array_filter([
					!empty($item->person_name) ? ['@type' => 'Person', 'name' => (string) $item->person_name] : null,
					!empty($item->team_name) ? ['@type' => 'SportsTeam', 'name' => (string) $item->team_name] : null,
				])),
			],
			$this->items
		),
		$this->projectLabel()
	),
]);
?>
<div class="com-joomleague-site">
	<?php if ($showSectionheader) : ?>
	<section class="jl-site-hero mb-4"><div class="jl-site-eyebrow"><?php echo $this->escape($this->projectLabel()); ?></div><h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_STATS'); ?></h1></section>
	<?php endif; ?>
	<div class="jl-site-panel table-responsive"><table class="table jl-site-table"><thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_STATISTIC'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_SITE_VALUE'); ?></th></tr></thead><tbody><?php foreach ($this->items as $item) : ?><tr><td><?php echo $this->escape($item->statistic_name); ?></td><td><?php echo $this->escape($item->person_name ?? ''); ?></td><td><?php echo $this->escape($item->team_name ?? ''); ?></td><td><strong><?php echo $this->escape((string) $item->value); ?></strong></td></tr><?php endforeach; ?></tbody></table><?php if (!$this->items) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?></div>
</div>
