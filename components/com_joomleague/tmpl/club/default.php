<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$club = $this->item;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';
$address = $club ? trim(($club->address ?? '') . ', ' . ($club->zipcode ?? '') . ' ' . ($club->location ?? ''), ' ,') : '';
$clubText = $club ? trim((string) ($club->notes ?? '')) : '';

if ($club) {
	StructuredDataHelper::add($this->getDocument(), [
		'@context' => 'https://schema.org',
		'@type' => 'SportsOrganization',
		'@id' => StructuredDataHelper::currentUrl() . '#club',
		'name' => (string) $club->name,
		'url' => StructuredDataHelper::absoluteUrl($club->website ?? null),
		'email' => $club->email ?: null,
		'sport' => $club->sport_name ?? null,
		'address' => [
			'@type' => 'PostalAddress',
			'streetAddress' => $club->address ?? null,
			'postalCode' => $club->zipcode ?? null,
			'addressLocality' => $club->location ?? null,
			'addressCountry' => $club->country ?? null,
		],
		'location' => $club->playground_name ? [
			'@type' => 'SportsActivityLocation',
			'name' => (string) $club->playground_name,
		] : null,
	]);
}
?>
<div class="com-joomleague-site">
	<?php if (!$club) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUB_NOT_FOUND'); ?></div><?php return; endif; ?>
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUB'); ?></div>
		<h1 class="jl-site-title"><?php echo $this->escape($club->name); ?></h1>
		<p class="jl-site-muted mb-3"><?php echo $this->escape($address); ?></p>
		<nav class="jl-site-nav">
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=schedule&club_id=' . (int) $club->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE'); ?></a>
			<?php if ($club->standard_playground) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=playground&id=' . (int) $club->standard_playground); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND'); ?></a><?php endif; ?>
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
	<?php if ($clubText !== '') : ?>
		<div class="jl-site-panel mb-4">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_SUMMARY'); ?></h2>
			<p class="jl-site-muted mb-0"><?php echo nl2br($this->escape($clubText)); ?></p>
		</div>
	<?php endif; ?>
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
	<?php if ($this->clubPlaygrounds) : ?>
		<div class="jl-site-panel table-responsive">
			<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_VENUES'); ?></h2>
			<table class="table jl-site-table align-middle">
				<tbody>
					<?php foreach ($this->clubPlaygrounds as $playground) : ?>
						<tr>
							<td><?php echo $this->escape($playground->name); ?></td>
							<td><?php echo $this->escape(trim(($playground->address ?? '') . ', ' . ($playground->zipcode ?? '') . ' ' . ($playground->city ?? ''), ' ,')); ?></td>
							<td><?php echo $this->escape((string) ($playground->max_visitors ?? '')); ?></td>
							<td><a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=playground&id=' . (int) $playground->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
