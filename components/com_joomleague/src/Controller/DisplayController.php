<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

final class DisplayController extends BaseController
{
	protected $default_view = 'standings';

	public function display($cachable = false, $urlparams = [])
	{
		parent::display($cachable, $urlparams);

		if ($this->app->getDocument()->getType() === 'html') {
			// Product attribution is intentionally language-independent brand text.
			echo '<footer class="mt-4 pt-3 border-top text-center small text-body-secondary">'
				. '<a class="link-secondary text-decoration-none" href="'
				. htmlspecialchars(Route::_('index.php?option=com_joomleague&view=aboutjoomleague', false, Route::TLS_IGNORE, false), ENT_QUOTES, 'UTF-8') . '">'
				. 'Powered by JoomLeague</a>'
				. '</footer>';
		}

		return $this;
	}
}
