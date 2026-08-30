<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

final class IcalController extends BaseController
{
	public function download(): void
	{
		$calendar = $this->getModel('Ical')->getCalendar();
		if (isset($calendar['error'])) {
			throw new \RuntimeException(Text::_($calendar['error']), 404);
		}

		$this->app->setHeader('Content-Type', 'text/calendar; charset=utf-8', true)
			->setHeader('Content-Disposition', 'attachment; filename="' . $calendar['filename'] . '"', true)
			->setHeader('Content-Length', (string) strlen($calendar['content']), true)
			->setHeader('Cache-Control', 'public, max-age=900', true)
			->setHeader('X-Content-Type-Options', 'nosniff', true)
			->sendHeaders();
		echo $calendar['content'];
		$this->app->close();
	}
}
