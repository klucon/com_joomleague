<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Venues\HtmlView $this */
$itemId = Factory::getApplication()->getInput()->getInt('Itemid', 0);
?>
<div class="com-joomleague-venues">
	<h1><?php echo Text::_('COM_JOOMLEAGUE_VENUES_VIEW_TITLE'); ?></h1>
	<p class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_VENUES_VIEW_INTRO'); ?></p>
	<form class="row g-2 align-items-end mb-4" action="<?php echo Route::_('index.php?option=com_joomleague&view=venues' . ($itemId ? '&Itemid=' . $itemId : '')); ?>" method="get">
		<div class="col-12 col-md"><label class="form-label" for="filter_search"><?php echo Text::_('JSEARCH_FILTER_LABEL'); ?></label><input class="form-control" type="search" id="filter_search" name="filter_search" value="<?php echo htmlspecialchars($this->search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo Text::_('COM_JOOMLEAGUE_VENUES_SEARCH_PLACEHOLDER'); ?>"></div>
		<div class="col-12 col-md-4"><label class="form-label" for="filter_country_code"><?php echo Text::_('COM_JOOMLEAGUE_VENUES_COUNTRY_FILTER'); ?></label><select class="form-select" id="filter_country_code" name="filter_country_code"><option value=""><?php echo Text::_('COM_JOOMLEAGUE_VENUES_ALL_COUNTRIES'); ?></option><?php foreach ($this->countries as $country) : ?><option value="<?php echo htmlspecialchars((string) $country->country_code, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $this->countryCode === (string) $country->country_code ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $country->country_code, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
		<div class="col-12 col-md-auto d-flex gap-2"><button class="btn btn-primary" type="submit"><span class="icon-search" aria-hidden="true"></span> <?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button><a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=venues' . ($itemId ? '&Itemid=' . $itemId : '')); ?>"><span class="icon-refresh" aria-hidden="true"></span> <?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></a></div>
		<input type="hidden" name="option" value="com_joomleague"><input type="hidden" name="view" value="venues"><?php if ($itemId) : ?><input type="hidden" name="Itemid" value="<?php echo $itemId; ?>"><?php endif; ?>
	</form>
	<?php if ($this->venues === []) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_VENUES_VIEW_EMPTY'); ?></div><?php else : ?>
		<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
			<?php foreach ($this->venues as $venue) : ?><div class="col"><article class="card h-100"><?php if ($venue->picture) : ?><img src="<?php echo htmlspecialchars((string) $venue->picture, ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top" alt="" loading="lazy"><?php endif; ?><div class="card-body"><h2 class="h5 mb-1"><a class="stretched-link text-decoration-none" href="<?php echo Route::_('index.php?option=com_joomleague&view=venue&venue_id=' . (int) $venue->id); ?>"><?php echo htmlspecialchars((string) $venue->name, ENT_QUOTES, 'UTF-8'); ?></a></h2><div class="text-body-secondary"><?php echo htmlspecialchars(trim((string) $venue->city . (($venue->city && $venue->country_code) ? ', ' : '') . (string) $venue->country_code), ENT_QUOTES, 'UTF-8'); ?></div><div class="d-flex flex-wrap gap-3 mt-2 small text-body-secondary"><?php if ($venue->capacity !== null) : ?><span><?php echo Text::sprintf('COM_JOOMLEAGUE_VENUE_CAPACITY', (int) $venue->capacity); ?></span><?php endif; ?><?php if ($venue->club_name) : ?><span><?php echo htmlspecialchars((string) $venue->club_name, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></div></div></article></div><?php endforeach; ?>
		</div>
		<?php if ($this->pagination->pagesTotal > 1) : ?><nav class="mt-4" aria-label="<?php echo Text::_('JLIB_HTML_PAGINATION'); ?>"><?php echo $this->pagination->getPagesLinks(); ?></nav><?php endif; ?>
	<?php endif; ?>
</div>
