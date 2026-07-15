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
use Joomla\CMS\Uri\Uri;

$params = $this->templateParams;
$showSectionheader = (bool) ($params['show_sectionheader'] ?? true);
$showTeamPicture = (bool) ($params['show_team_picture'] ?? true);
$showClubPicture = (bool) ($params['show_club_picture'] ?? true);

$teamLogo = static function (object $team) use ($showTeamPicture, $showClubPicture): string {
	$pic = trim((string) (($showTeamPicture ? ($team->team_picture ?? '') : '') ?: ($showClubPicture ? ($team->club_logo_small ?? '') : '')));

	return $pic !== '' ? (preg_match('#^https?://#i', $pic) ? $pic : Uri::root(true) . '/' . ltrim($pic, '/')) : '';
};
?>
<div class="com-joomleague-site">
	<?php if ($showSectionheader) : ?>
	<section class="jl-site-hero mb-4"><div class="jl-site-eyebrow"><?php echo $this->project ? $this->escape($this->project->name) : ''; ?></div><h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAMS'); ?></h1></section>
	<?php endif; ?>
	<div class="jl-site-grid">
		<?php foreach ($this->teams as $team) : ?>
			<a class="jl-site-card" href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->id); ?>">
				<span><?php if ($teamLogo($team) !== '') : ?><img src="<?php echo $this->escape($teamLogo($team)); ?>" alt="" loading="lazy" style="max-height:24px;width:auto;vertical-align:middle;"> <?php endif; ?><strong><?php echo $this->escape($team->team_name); ?></strong><br><span class="jl-site-muted"><?php echo $this->escape($team->club_name ?? ''); ?></span></span>
				<span class="jl-site-badge"><?php echo $this->escape($team->division_name ?? Text::_('COM_JOOMLEAGUE_SITE_NO_DIVISION')); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php if (!$this->teams) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
</div>
