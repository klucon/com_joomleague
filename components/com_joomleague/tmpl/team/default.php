<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$team = $this->item;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';

if ($team) {
	StructuredDataHelper::add($this->getDocument(), [
		'@context' => 'https://schema.org',
		'@type' => 'SportsOrganization',
		'@id' => StructuredDataHelper::currentUrl() . '#team',
		'name' => (string) $team->team_name,
		'alternateName' => $team->team_short_name ?? null,
		'url' => StructuredDataHelper::absoluteUrl($team->team_website ?? null),
		'sport' => $this->project->sport_name ?? null,
		'parentOrganization' => $team->club_name ? [
			'@type' => 'SportsOrganization',
			'name' => (string) $team->club_name,
		] : null,
		'location' => $team->playground_name ? [
			'@type' => 'SportsActivityLocation',
			'name' => (string) $team->playground_name,
		] : null,
	]);
}
?>
<div class="com-joomleague-site">
	<?php if (!$team) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_NOT_FOUND'); ?></div><?php return; endif; ?>
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo LayoutHelper::render('joomleague.flag', ['code' => $team->club_country ?? '', 'showName' => false, 'size' => 18], $jlFlagPath); ?> <?php echo $this->escape($team->club_name ?? ''); ?></div>
		<h1 class="jl-site-title"><?php echo $this->escape($team->team_name); ?></h1>
		<nav class="jl-site-nav mt-3">
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=roster&id=' . (int) $team->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ROSTER'); ?></a>
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=schedule&project_id=' . (int) $team->project_id . '&projectteam_id=' . (int) $team->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE'); ?></a>
			<?php if ($team->club_id) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=club&id=' . (int) $team->club_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUB'); ?></a><?php endif; ?>
			<?php if ($team->playground_id) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=playground&id=' . (int) $team->playground_id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND'); ?></a><?php endif; ?>
		</nav>
	</section>
	<div class="jl-site-panel mb-4">
		<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM_INFO'); ?></h2>
		<p class="jl-site-muted"><?php echo nl2br($this->escape(trim((string) ($team->team_info ?: $team->team_notes)))); ?></p>
	</div>
	<div class="jl-site-panel"><h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCHES'); ?></h2><?php require JPATH_COMPONENT . '/tmpl/results/matches.php'; ?></div>
</div>
