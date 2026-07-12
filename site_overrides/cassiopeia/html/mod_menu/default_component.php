<?php

/**
 * Joomla menu component item override.
 * Re-routes JoomLeague component menu items so project scoped menu links use
 * the component canonical SEF path instead of the stored menu alias path.
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Filter\OutputFilter;

$attributes = [];

if ($item->anchor_title) {
	$attributes['title'] = $item->anchor_title;
}

if ($item->anchor_css) {
	$attributes['class'] = $item->anchor_css;
}

if ($item->anchor_rel) {
	$attributes['rel'] = $item->anchor_rel;
}

if ($item->id == $active_id) {
	$attributes['aria-current'] = 'location';

	if ($item->current) {
		$attributes['aria-current'] = 'page';
	}
}

$itemTitle = Text::_($item->title);
$linktype = $itemTitle;

if ($item->menu_icon) {
	if ($itemParams->get('menu_text', 1)) {
		$linktype = '<span class="p-2 pt-0 ' . $item->menu_icon . '" aria-hidden="true"></span>' . $itemTitle;
	} else {
		$linktype = '<span class="p-2 pt-0 ' . $item->menu_icon . '" aria-hidden="true"></span><span class="visually-hidden">' . $itemTitle . '</span>';
	}
} elseif ($item->menu_image) {
	$imageAttributes = [];

	if ($item->menu_image_css) {
		$imageAttributes['class'] = $item->menu_image_css;
	}

	$linktype = HTMLHelper::_('image', $item->menu_image, '', $imageAttributes);
	$linktype .= '<span class="image-title' . ($itemParams->get('menu_text', 1) ? '' : ' visually-hidden') . '">' . $itemTitle . '</span>';
}

if ($item->browserNav == 1) {
	$attributes['target'] = '_blank';
} elseif ($item->browserNav == 2) {
	$options = 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes';
	$attributes['onclick'] = "window.open(this.href, 'targetWindow', '" . $options . "'); return false;";
}

$link = $item->flink;
$query = [];
parse_str((string) parse_url((string) $item->link, PHP_URL_QUERY), $query);

if (($query['option'] ?? '') === 'com_joomleague') {
	$routeQuery = $query;
	unset($routeQuery['Itemid']);

	$view = (string) ($routeQuery['view'] ?? '');
	$projectViews = [
		'project',
		'ranking',
		'results',
		'schedule',
		'teams',
		'team',
		'roster',
		'rivals',
		'teamstats',
		'referees',
		'stats',
		'resultsmatrix',
		'resultsranking',
		'statsranking',
		'eventsranking',
		'curve',
		'nextmatch',
		'ical',
		'prediction',
		'treetonode',
		'raceresults',
		'matchreport',
		'person',
	];

	if (in_array($view, $projectViews, true) && $view !== 'projects') {
		$menu = Factory::getApplication()->getMenu('site');
		$base = null;

		foreach ((array) $menu->getItems(['component'], ['com_joomleague']) as $menuItem) {
			if (($menuItem->query['view'] ?? '') === 'projects') {
				$base = $menuItem;
				break;
			}
		}

		if ($base) {
			$routeQuery['Itemid'] = $base->id;
		}
	} elseif (in_array($view, ['club', 'playground'], true)) {
		$routeQuery['Itemid'] = $item->id;
	}

	$link = Route::_('index.php?' . http_build_query($routeQuery, '', '&', PHP_QUERY_RFC3986), false);
}

echo HTMLHelper::_('link', OutputFilter::ampReplace(htmlspecialchars($link, ENT_COMPAT, 'UTF-8', false)), $linktype, $attributes);
