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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';

$params = $this->templateParams;
$showSectionheader = (bool) ($params['show_sectionheader'] ?? true);
$showAddress = (bool) ($params['show_address'] ?? true);
$showClubTeams = (bool) ($params['show_club_teams'] ?? true);
$showSmallLogo = (bool) ($params['show_small_logo'] ?? true);
$showMediumLogo = (bool) ($params['show_medium_logo'] ?? false);
$showBigLogo = (bool) ($params['show_big_logo'] ?? false);
$showLogo = $showSmallLogo || $showMediumLogo || $showBigLogo;

$clubLogo = static function (object $club) use ($showSmallLogo, $showMediumLogo, $showBigLogo): string {
	$pic = trim((string) ($showBigLogo ? ($club->logo_big ?? '') : ($showMediumLogo ? ($club->logo_middle ?? '') : ($club->logo_small ?? ''))));

	return $pic !== '' ? (preg_match('#^https?://#i', $pic) ? $pic : Uri::root(true) . '/' . ltrim($pic, '/')) : '';
};
?>
<div class="com-joomleague-site">
	<?php if ($showSectionheader) : ?>
	<section class="jl-site-hero mb-4"><div class="jl-site-eyebrow"><?php echo Text::_('COM_JOOMLEAGUE_SITE_DIRECTORY'); ?></div><h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUBS'); ?></h1></section>
	<?php endif; ?>
	<div class="jl-site-grid"><?php foreach ($this->items as $club) : ?><a class="jl-site-card" href="<?php echo Route::_('index.php?option=com_joomleague&view=club&id=' . (int) $club->id); ?>"><span><?php if ($showLogo && $clubLogo($club) !== '') : ?><img src="<?php echo $this->escape($clubLogo($club)); ?>" alt="" loading="lazy" style="max-height:24px;width:auto;vertical-align:middle;"> <?php endif; ?><strong><?php echo LayoutHelper::render('joomleague.flag', ['code' => $club->country ?? '', 'showName' => false, 'size' => 18], $jlFlagPath); ?> <?php echo $this->escape($club->name); ?></strong><?php if ($showAddress) : ?><br><span class="jl-site-muted"><?php echo $this->escape(trim(($club->location ?? '') . ' ' . ($club->zipcode ?? ''))); ?></span><?php endif; ?></span><?php if ($showClubTeams) : ?><span class="jl-site-badge"><?php echo (int) $club->teams . ' ' . Text::_('COM_JOOMLEAGUE_SITE_TEAMS'); ?></span><?php endif; ?></a><?php endforeach; ?></div>
	<?php if (!$this->items) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
</div>
