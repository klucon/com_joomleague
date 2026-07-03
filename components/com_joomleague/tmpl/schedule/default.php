<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$title = Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE');
$eyebrow = $this->project ? $this->project->name : '';

if ($this->scheduleTeam) {
	$title = $this->scheduleTeam->team_name;
	$eyebrow = Text::_('COM_JOOMLEAGUE_SITE_TEAM') . ' · ' . Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE');
} elseif ($this->scheduleClub) {
	$title = $this->scheduleClub->name;
	$eyebrow = Text::_('COM_JOOMLEAGUE_SITE_CLUB') . ' · ' . Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE');
}

$icalQuery = 'index.php?option=com_joomleague&view=ical';

if ($this->project) {
	$icalQuery .= '&project_id=' . (int) $this->project->id;
}

if ($this->scheduleTeam) {
	$icalQuery .= '&projectteam_id=' . (int) $this->scheduleTeam->id;
}

if ($this->scheduleClub) {
	$icalQuery .= '&club_id=' . (int) $this->scheduleClub->id;
}

$icalPath = Route::_($icalQuery, false);
$icalUrl = Uri::root() . ltrim($icalPath, '/');
$webcalUrl = preg_replace('#^https?://#', 'webcal://', $icalUrl);
$calendarName = $title !== '' ? $title : Text::_('COM_JOOMLEAGUE_SITE_SCHEDULE');
?>
<div class="com-joomleague-site">
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->escape($eyebrow); ?></div>
		<h1 class="jl-site-title"><?php echo $this->escape($title); ?></h1>
		<nav class="jl-site-nav mt-3">
			<?php if ($this->project) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $this->project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></a><?php endif; ?>
			<?php if ($this->scheduleTeam) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $this->scheduleTeam->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></a><?php endif; ?>
			<?php if ($this->scheduleClub) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=club&id=' . (int) $this->scheduleClub->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUB'); ?></a><?php endif; ?>
			<?php if ($this->project) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=results&project_id=' . (int) $this->project->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_RESULTS'); ?></a><?php endif; ?>
		</nav>
	</section>
	<div class="jl-site-grid mb-4">
		<div class="jl-site-card"><strong><?php echo (int) ($this->matchSummary['total'] ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCHES'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo (int) ($this->matchSummary['played'] ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYED_MATCHES'); ?></span></div>
		<div class="jl-site-card"><strong><?php echo (int) ($this->matchSummary['upcoming'] ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_UPCOMING_MATCHES'); ?></span></div>
		<?php if ($this->scheduleTeam) : ?>
			<div class="jl-site-card"><strong><?php echo (int) ($this->matchSummary['home'] ?? 0); ?> / <?php echo (int) ($this->matchSummary['away'] ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_HOME_AWAY'); ?></span></div>
		<?php endif; ?>
	</div>
	<div class="jl-site-panel mb-4">
		<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_ADD_TO_CALENDAR'); ?></h2>
		<p class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CALENDAR_SUBSCRIBE_HELP'); ?></p>
		<nav class="jl-site-nav">
			<a href="<?php echo $this->escape($icalUrl); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DOWNLOAD_ICS'); ?><br><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_ONE_TIME_IMPORT'); ?></span></a>
			<a href="https://calendar.google.com/calendar/render?cid=<?php echo rawurlencode($icalUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_SITE_GOOGLE_CALENDAR'); ?></a>
			<a href="<?php echo $this->escape($webcalUrl); ?>"><?php echo Text::_('COM_JOOMLEAGUE_SITE_APPLE_CALENDAR'); ?></a>
			<a href="https://outlook.live.com/calendar/0/addfromweb?url=<?php echo rawurlencode($icalUrl); ?>&amp;name=<?php echo rawurlencode($calendarName); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_SITE_OUTLOOK_COM'); ?></a>
			<a href="https://outlook.office.com/calendar/0/addfromweb?url=<?php echo rawurlencode($icalUrl); ?>&amp;name=<?php echo rawurlencode($calendarName); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_SITE_OFFICE_365'); ?></a>
		</nav>
	</div>
	<div class="jl-site-panel">
		<h2><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCHES'); ?></h2>
		<?php require JPATH_COMPONENT . '/tmpl/results/matches.php'; ?>
	</div>
</div>
