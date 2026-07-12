<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomleague\Component\Joomleague\Site\Service\IcalFeedHelper;

$app = Factory::getApplication();
$host = (string) (parse_url(Uri::root(), PHP_URL_HOST) ?: '');
$calendarName = $this->project->name ?? $this->scheduleTeam->team_name ?? $this->scheduleClub->name ?? 'JoomLeague';
$filename = preg_replace('/[^a-z0-9-]+/i', '-', strtolower((string) $calendarName)) ?: 'joomleague';
$content = IcalFeedHelper::render($this->matches, (string) $calendarName, $host);

$app->clearHeaders();
$app->setHeader('Content-Type', 'text/calendar; charset=utf-8', true);
$app->setHeader('Content-Disposition', 'inline; filename="' . $filename . '.ics"', true);
$app->setHeader('Cache-Control', 'no-cache, must-revalidate', true);

echo $content;

$app->close();
