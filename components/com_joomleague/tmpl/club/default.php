<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$club = $this->item;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';

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
	<section class="jl-site-hero mb-4"><div class="jl-site-eyebrow"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUB'); ?></div><h1 class="jl-site-title"><?php echo $this->escape($club->name); ?></h1><p class="jl-site-muted mb-0"><?php echo $this->escape(trim(($club->address ?? '') . ', ' . ($club->zipcode ?? '') . ' ' . ($club->location ?? ''), ' ,')); ?></p></section>
	<div class="jl-site-grid mb-4">
		<div class="jl-site-card"><strong><?php echo $this->escape($club->website ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_WEBSITE'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo $this->escape($club->email ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_EMAIL'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo $this->escape($club->playground_name ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND'); ?></span></div>
		<?php if (!empty($club->country)) : ?><div class="jl-site-card"><strong><?php echo LayoutHelper::render('joomleague.flag', ['code' => $club->country], $jlFlagPath); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_COUNTRY'); ?></span></div><?php endif; ?>
	</div>
	<div class="jl-site-panel table-responsive"><h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAMS'); ?></h2><table class="table jl-site-table"><tbody><?php foreach ($this->items as $team) : ?><tr><td><?php echo $this->escape($team->name); ?></td><td><?php echo $this->escape($team->project_name ?? ''); ?></td><td><?php if ($team->projectteam_id) : ?><a class="jl-site-button" href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->projectteam_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DETAIL'); ?></a><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
</div>
