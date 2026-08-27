<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Clubs\HtmlView $this */
$itemId = Factory::getApplication()->getInput()->getInt('Itemid', 0);
?>
<div class="com-joomleague-clubs">
	<h1><?php echo Text::_('COM_JOOMLEAGUE_CLUBS_VIEW_TITLE'); ?></h1>
	<p class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_CLUBS_VIEW_INTRO'); ?></p>

	<form class="row g-2 align-items-end mb-4" action="<?php echo Route::_('index.php?option=com_joomleague&view=clubs' . ($itemId ? '&Itemid=' . $itemId : '')); ?>" method="get">
		<div class="col-12 col-md">
			<label class="form-label" for="filter_search"><?php echo Text::_('JSEARCH_FILTER_LABEL'); ?></label>
			<input class="form-control" type="search" id="filter_search" name="filter_search" value="<?php echo htmlspecialchars($this->search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo Text::_('COM_JOOMLEAGUE_CLUBS_SEARCH_PLACEHOLDER'); ?>">
		</div>
		<div class="col-12 col-md-4">
			<label class="form-label" for="filter_country_code"><?php echo Text::_('COM_JOOMLEAGUE_CLUBS_COUNTRY_FILTER'); ?></label>
			<select class="form-select" id="filter_country_code" name="filter_country_code">
				<option value=""><?php echo Text::_('COM_JOOMLEAGUE_CLUBS_ALL_COUNTRIES'); ?></option>
				<?php foreach ($this->countries as $country) : ?><option value="<?php echo htmlspecialchars((string) $country->country_code, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $this->countryCode === (string) $country->country_code ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $country->country_code, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
			</select>
		</div>
		<div class="col-12 col-md-auto d-flex gap-2">
			<button class="btn btn-primary" type="submit"><span class="icon-search" aria-hidden="true"></span> <?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			<a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=clubs' . ($itemId ? '&Itemid=' . $itemId : '')); ?>"><span class="icon-refresh" aria-hidden="true"></span> <?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
		</div>
		<input type="hidden" name="option" value="com_joomleague">
		<input type="hidden" name="view" value="clubs">
		<?php if ($itemId) : ?><input type="hidden" name="Itemid" value="<?php echo $itemId; ?>"><?php endif; ?>
	</form>

	<?php if ($this->clubs === []) : ?>
		<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_CLUBS_VIEW_EMPTY'); ?></div>
	<?php else : ?>
		<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
			<?php foreach ($this->clubs as $club) : ?>
				<div class="col"><article class="card h-100"><div class="card-body d-flex gap-3 align-items-start">
					<?php if ($club->logo) : ?><img src="<?php echo htmlspecialchars((string) $club->logo, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="img-fluid" width="72" height="72" loading="lazy"><?php else : ?><span class="icon-shield fs-2 text-body-secondary" aria-hidden="true"></span><?php endif; ?>
					<div class="flex-grow-1"><h2 class="h5 mb-1"><a class="stretched-link text-decoration-none" href="<?php echo Route::_('index.php?option=com_joomleague&view=club&club_id=' . (int) $club->id); ?>"><?php echo htmlspecialchars((string) $club->name, ENT_QUOTES, 'UTF-8'); ?></a></h2><?php if ($club->short_name) : ?><div class="text-body-secondary"><?php echo htmlspecialchars((string) $club->short_name, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?><div class="d-flex flex-wrap gap-3 mt-2 small text-body-secondary"><?php if ($club->country_code) : ?><span><?php echo htmlspecialchars((string) $club->country_code, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?><span><?php echo Text::sprintf('COM_JOOMLEAGUE_CLUBS_TEAM_COUNT', (int) $club->team_count); ?></span></div></div>
				</div></article></div>
			<?php endforeach; ?>
		</div>
		<?php if ($this->pagination->pagesTotal > 1) : ?><nav class="mt-4" aria-label="<?php echo Text::_('JLIB_HTML_PAGINATION'); ?>"><?php echo $this->pagination->getPagesLinks(); ?></nav><?php endif; ?>
	<?php endif; ?>
</div>
