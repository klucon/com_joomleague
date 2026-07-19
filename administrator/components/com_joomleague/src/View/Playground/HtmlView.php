<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Playground;

\defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_PLAYGROUND_NEW',
			'edit' => 'COM_JOOMLEAGUE_PLAYGROUND_EDIT',
			'icon' => 'location',
			'singular' => 'playground',
			'details' => 'COM_JOOMLEAGUE_PLAYGROUND_DETAILS',
				'main' => ['name', 'short_name', 'alias', 'address', 'zipcode', 'city', 'country', 'geocode', 'latitude', 'longitude', 'max_visitors', 'website', 'club_id', 'picture', 'info', 'notes', 'extended'],
			'side' => [],
			'publishing' => ['ordering', 'id'],
		];
	}

	public function display($tpl = null): void
	{
		$document = $this->getDocument();
		$document->addStyleSheet(Uri::root(true) . '/media/com_joomleague/vendor/leaflet/leaflet.css?v=1.9.4');
		$document->addScript(Uri::root(true) . '/media/com_joomleague/vendor/leaflet/leaflet.js?v=1.9.4');
		$document->addScript(Uri::root(true) . '/media/com_joomleague/js/geocode-button.js?v=2.0.0', [], ['defer' => true]);
		parent::display($tpl);
	}
}
