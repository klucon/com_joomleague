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
use Joomla\CMS\Uri\Uri;
use Joomleague\Component\Joomleague\Site\Service\MapUrlHelper;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$item = $this->item;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';

$params = $this->templateParams;
$showSectionheader = (bool) ($params['show_sectionheader'] ?? true);
$showMaps = (bool) ($params['show_maps'] ?? true);
$showMapEmbed = (bool) ($params['show_map_embed'] ?? false);
$address = $item ? trim(($item->address ?? '') . ', ' . ($item->zipcode ?? '') . ' ' . ($item->city ?? ''), ' ,') : '';
$mapUrl = $item ? MapUrlHelper::build($address, $item->latitude !== null ? (float) $item->latitude : null, $item->longitude !== null ? (float) $item->longitude : null) : '';

if ($showMapEmbed && $item && $item->latitude !== null && $item->longitude !== null) {
	$document = $this->getDocument();
	$document->addStyleSheet(Uri::root(true) . '/media/com_joomleague/vendor/leaflet/leaflet.css?v=1.9.4');
	$document->addScript(Uri::root(true) . '/media/com_joomleague/vendor/leaflet/leaflet.js?v=1.9.4');
	$document->addScript(Uri::root(true) . '/media/com_joomleague/js/map-embed.js?v=1.0.0', [], ['defer' => true]);
} else {
	$showMapEmbed = false;
}

if ($item) {
	StructuredDataHelper::add($this->getDocument(), [
		'@context' => 'https://schema.org',
		'@type' => 'SportsActivityLocation',
		'@id' => StructuredDataHelper::currentUrl() . '#venue',
		'name' => (string) $item->name,
		'url' => StructuredDataHelper::absoluteUrl($item->website ?? null),
		'maximumAttendeeCapacity' => !empty($item->max_visitors) ? (int) $item->max_visitors : null,
		'address' => [
			'@type' => 'PostalAddress',
			'streetAddress' => $item->address ?? null,
			'postalCode' => $item->zipcode ?? null,
			'addressLocality' => $item->city ?? null,
			'addressCountry' => $item->country ?? null,
		],
	]);
}
?>
<div class="com-joomleague-site">
	<?php if (!$item) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND_NOT_FOUND'); ?></div><?php return; endif; ?>
	<?php if ($showSectionheader) : ?>
	<section class="jl-site-hero mb-4"><div class="jl-site-eyebrow"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND'); ?></div><h1 class="jl-site-title"><?php echo $this->escape($item->name); ?></h1><p class="jl-site-muted mb-0"><?php echo $this->escape($address); ?></p><?php if ($showMaps && $address !== '') : ?><p class="mb-0 mt-2"><a href="<?php echo $this->escape($mapUrl); ?>" target="_blank" rel="noopener"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SHOW_ON_MAP'); ?></a></p><?php endif; ?></section>
	<?php endif; ?>
	<div class="jl-site-grid"><div class="jl-site-card"><strong><?php echo $this->escape($item->club_name ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUB'); ?></span></div><div class="jl-site-card"><strong><?php echo (int) ($item->max_visitors ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CAPACITY'); ?></span></div><div class="jl-site-card"><strong><?php echo $this->escape($item->website ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_WEBSITE'); ?></span></div><?php if (!empty($item->country)) : ?><div class="jl-site-card"><strong><?php echo LayoutHelper::render('joomleague.flag', ['code' => $item->country], $jlFlagPath); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_COUNTRY'); ?></span></div><?php endif; ?></div>
	<?php if ($showMapEmbed) : ?>
		<div class="jl-site-panel mt-4">
			<div class="jl-map-embed" data-lat="<?php echo $this->escape((string) $item->latitude); ?>" data-lng="<?php echo $this->escape((string) $item->longitude); ?>" style="height:320px;"></div>
		</div>
	<?php endif; ?>
</div>
