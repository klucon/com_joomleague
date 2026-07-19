<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomleague\Component\Joomleague\Site\Service\MapUrlHelper;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$club = $this->item;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';
$address = $club ? trim(($club->address ?? '') . ', ' . ($club->zipcode ?? '') . ' ' . ($club->location ?? ''), ' ,') : '';
$clubText = $club ? trim((string) ($club->info ?? '')) : '';
$translateLegacyName = static function ($value): string {
	$value = trim((string) $value);

	return preg_match('/^(COM|JLM)_[A-Z0-9_-]+$/', $value) ? Text::_($value) : $value;
};
$sportName = $club ? $translateLegacyName($club->sport_name ?? '') : '';

// Logo klubu (cesta relativní ke kořeni Joomly, nebo absolutní URL).
$clubLogo = '';
$schemaClubLogo = null;
if ($club) {
	$logo = trim((string) ($club->logo_big ?: $club->logo_middle ?: $club->logo_small ?: ''));
	$schemaClubLogo = $logo !== '' ? (preg_match('#^https?://#i', $logo) ? $logo : Uri::root(true) . '/' . ltrim($logo, '/')) : null;

	if ($logo === '') {
		$logo = trim((string) ComponentHelper::getParams('com_joomleague')->get('placeholder_club_logo', ''));
	}
	if ($logo !== '') {
		$clubLogo = preg_match('#^https?://#i', $logo) ? $logo : Uri::root(true) . '/' . ltrim($logo, '/');
	}
}
$mapUrl = static fn (string $query, ?float $lat = null, ?float $lng = null): string => MapUrlHelper::build($query, $lat, $lng);

$params = $this->templateParams;
$showDescription = (bool) ($params['show_description'] ?? true);
$showMaps = (bool) ($params['show_maps'] ?? true);
$showMapEmbed = (bool) ($params['show_map_embed'] ?? false);
$showTeamsOfClub = (bool) ($params['show_teams_of_club'] ?? true);
$showClubLogo = (bool) ($params['show_club_logo'] ?? true);
$showPlaygroundsOfClub = (bool) ($params['show_playgrounds_of_club'] ?? true);

if ($showMapEmbed && $club && $club->latitude !== null && $club->longitude !== null) {
	$document = $this->getDocument();
	$document->addStyleSheet(Uri::root(true) . '/media/com_joomleague/vendor/leaflet/leaflet.css?v=1.9.4');
	$document->addScript(Uri::root(true) . '/media/com_joomleague/vendor/leaflet/leaflet.js?v=1.9.4');
	$document->addScript(Uri::root(true) . '/media/com_joomleague/js/map-embed.js?v=1.0.0', [], ['defer' => true]);
} else {
	$showMapEmbed = false;
}

if (!$showClubLogo) {
	$clubLogo = '';
}

if ($club) {
	$clubUrl = StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=club&id=' . (int) $club->id, false));
	$playgroundUrl = !empty($club->standard_playground) ? StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=playground&id=' . (int) $club->standard_playground, false)) : null;
	StructuredDataHelper::add($this->getDocument(), [
		'@context' => 'https://schema.org',
		'@type' => 'SportsOrganization',
		'@id' => $clubUrl ? $clubUrl . '#club' : null,
		'name' => (string) $club->name,
		'url' => $clubUrl,
		'sameAs' => StructuredDataHelper::externalUrl($club->website ?? ''),
		'logo' => $schemaClubLogo,
		'image' => $schemaClubLogo,
		'description' => $clubText !== '' ? $clubText : null,
		'email' => $club->email ?: null,
		'telephone' => $club->phone ?: null,
		'faxNumber' => $club->fax ?: null,
		'foundingDate' => !empty($club->founded) ? (string) $club->founded : null,
		'sport' => $sportName !== '' ? $sportName : null,
		'mainEntityOfPage' => StructuredDataHelper::webPage((string) $club->name, $clubText !== '' ? $clubText : null, $clubUrl),
		'address' => [
			'@type' => 'PostalAddress',
			'streetAddress' => $club->address ?? null,
			'postalCode' => $club->zipcode ?? null,
			'addressLocality' => $club->location ?? null,
			'addressCountry' => $club->country ?? null,
		],
		'geo' => is_numeric($club->latitude ?? null) && is_numeric($club->longitude ?? null) ? [
			'@type' => 'GeoCoordinates',
			'latitude' => (float) $club->latitude,
			'longitude' => (float) $club->longitude,
		] : null,
		'location' => $club->playground_name ? [
			'@type' => 'SportsActivityLocation',
			'@id' => $playgroundUrl ? $playgroundUrl . '#venue' : null,
			'name' => (string) $club->playground_name,
			'url' => $playgroundUrl,
		] : null,
		'member' => array_map(
			static fn (object $team): array => [
				'@type' => 'SportsTeam',
				'@id' => !empty($team->projectteam_id) ? StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->projectteam_id, false)) . '#sportsteam' : null,
				'name' => (string) $team->name,
				'url' => !empty($team->projectteam_id) ? StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->projectteam_id, false)) : null,
			],
			$this->items
		),
	]);
}
?>
<div class="com-joomleague-site">
	<?php if (!$club) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUB_NOT_FOUND'); ?></div><?php return; endif; ?>
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUB'); ?></div>
		<div class="jl-club-head">
			<?php if ($clubLogo !== '') : ?><img class="jl-club-logo" src="<?php echo $this->escape($clubLogo); ?>" alt="<?php echo $this->escape($club->name); ?>" loading="lazy"><?php endif; ?>
			<div>
				<h1 class="jl-site-title mb-1"><?php echo $this->escape($club->name); ?></h1>
				<?php if ($address !== '') : ?><p class="jl-site-muted mb-0"><?php echo $this->escape($address); ?></p><?php endif; ?>
			</div>
		</div>
		<nav class="jl-site-nav mt-3">
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=schedule&club_id=' . (int) $club->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE'); ?></a>
			<?php if ($club->standard_playground) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=playground&id=' . (int) $club->standard_playground); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND'); ?></a><?php endif; ?>
			<?php if ($showMaps && $address !== '') : ?><a href="<?php echo $this->escape($mapUrl($address, $club->latitude !== null ? (float) $club->latitude : null, $club->longitude !== null ? (float) $club->longitude : null)); ?>" target="_blank" rel="noopener"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SHOW_ON_MAP'); ?></a><?php endif; ?>
		</nav>
	</section>
	<div class="jl-site-grid mb-4">
		<div class="jl-site-card"><strong><?php echo $this->escape($club->website ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_WEBSITE'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo $this->escape($club->email ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_EMAIL'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo $this->escape($club->playground_name ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND'); ?></span></div>
		<?php if (!empty($club->country)) : ?><div class="jl-site-card"><strong><?php echo LayoutHelper::render('joomleague.flag', ['code' => $club->country], $jlFlagPath); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_COUNTRY'); ?></span></div><?php endif; ?>
		<div class="jl-site-card"><strong><?php echo $this->escape($club->phone ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PHONE'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo $this->escape($club->president ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PRESIDENT'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo $this->escape($club->manager ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_MANAGER'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo count($this->items); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUB_TEAMS'); ?></span></div>
	</div>
	<?php if ($showMapEmbed) : ?>
		<div class="jl-site-panel mb-4">
			<div class="jl-map-embed" data-lat="<?php echo $this->escape((string) $club->latitude); ?>" data-lng="<?php echo $this->escape((string) $club->longitude); ?>" style="height:320px;"></div>
		</div>
	<?php endif; ?>
		<?php if ($showDescription && $clubText !== '') : ?>
			<div class="jl-site-panel mb-4">
				<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_SUMMARY'); ?></h2>
				<div class="jl-site-richtext"><?php echo HTMLHelper::_('content.prepare', $clubText); ?></div>
			</div>
		<?php endif; ?>
	<?php if ($showTeamsOfClub) : ?>
	<div class="jl-site-panel table-responsive mb-4">
		<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUB_TEAMS'); ?></h2>
		<table class="table jl-site-table align-middle">
			<thead>
				<tr>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_LEAGUE'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_SEASON'); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->items as $team) : ?>
					<tr>
						<td><?php echo $this->escape($team->name); ?></td>
						<td><?php echo $this->escape($team->project_name ?? ''); ?></td>
						<td><?php echo $this->escape($team->league_name ?? ''); ?></td>
						<td><?php echo $this->escape($team->season_name ?? ''); ?></td>
						<td><?php if ($team->projectteam_id) : ?><a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->projectteam_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></a><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if (!$this->items) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
	</div>
	<?php endif; ?>
	<?php if ($showPlaygroundsOfClub && $this->clubPlaygrounds) : ?>
		<div class="jl-site-panel table-responsive">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_VENUES'); ?></h2>
			<table class="table jl-site-table align-middle">
				<tbody>
					<?php foreach ($this->clubPlaygrounds as $playground) : ?>
						<?php $pgAddress = trim(($playground->address ?? '') . ', ' . ($playground->zipcode ?? '') . ' ' . ($playground->city ?? ''), ' ,'); ?>
						<tr>
							<td><?php echo $this->escape($playground->name); ?></td>
							<td><?php echo $this->escape($pgAddress); ?></td>
							<td><?php echo $this->escape((string) ($playground->max_visitors ?? '')); ?></td>
							<td class="jl-site-actions">
								<?php if ($showMaps && $pgAddress !== '') : ?><a class="jl-site-button" href="<?php echo $this->escape($mapUrl($pgAddress, $playground->latitude !== null ? (float) $playground->latitude : null, $playground->longitude !== null ? (float) $playground->longitude : null)); ?>" target="_blank" rel="noopener"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SHOW_ON_MAP'); ?></a><?php endif; ?>
								<a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=playground&id=' . (int) $playground->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
