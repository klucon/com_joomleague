<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

// Detail zápasu se renderuje přes sdílený, přepisovatelný layout (stejný, který
// používá i content plugin {jlmatch}). Override:
//   templates/<sablona>/html/layouts/joomleague/match/detail.php
echo LayoutHelper::render(
	'joomleague.match.detail',
	[
		'match'   => $this->item,
		'events'  => $this->items,
		'options' => ['link' => false, 'heading' => 'h1'],
	],
	JPATH_SITE . '/components/com_joomleague/layouts'
);
