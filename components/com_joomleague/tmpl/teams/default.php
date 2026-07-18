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
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$params = $this->templateParams;
$showSectionheader = (bool) ($params['show_sectionheader'] ?? true);
$showTeamPicture = (bool) ($params['show_team_picture'] ?? true);
$showClubPicture = (bool) ($params['show_club_picture'] ?? true);

$teamLogo = static function (object $team) use ($showTeamPicture, $showClubPicture): string {
	$pic = trim((string) (($showTeamPicture ? ($team->team_picture ?? '') : '') ?: ($showClubPicture ? ($team->club_logo_small ?? '') : '')));

	return $pic !== '' ? (preg_match('#^https?://#i', $pic) ? $pic : Uri::root(true) . '/' . ltrim($pic, '/')) : '';
};

StructuredDataHelper::add($this->getDocument(), [
	'@context' => 'https://schema.org',
] + StructuredDataHelper::collectionPage(
	Text::_('COM_JOOMLEAGUE_SITE_TEAMS'),
	array_map(
		static fn (object $team): array => [
			'@type' => 'SportsTeam',
			'@id' => StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->id, false)) . '#sportsteam',
			'name' => (string) $team->team_name,
			'url' => StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $team->id, false)),
			'logo' => StructuredDataHelper::imageUrl((string) (($team->team_picture ?? '') ?: ($team->club_logo_small ?? ''))),
			'parentOrganization' => !empty($team->club_name) ? [
				'@type' => 'SportsOrganization',
				'name' => (string) $team->club_name,
			] : null,
			'memberOf' => !empty($team->project_name) ? [
				'@type' => 'SportsOrganization',
				'name' => (string) $team->project_name,
			] : null,
		],
		$this->teams
	),
	$this->projectLabel()
));
?>
<div class="com-joomleague-site">
	<?php if ($showSectionheader) : ?>
	<section class="jl-site-hero mb-4"><div class="jl-site-eyebrow"><?php echo $this->escape($this->projectLabel()); ?></div><h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAMS'); ?></h1></section>
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
