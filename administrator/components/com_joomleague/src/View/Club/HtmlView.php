<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Club;

\defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_CLUB_NEW',
			'edit' => 'COM_JOOMLEAGUE_CLUB_EDIT',
			'icon' => 'home',
			'singular' => 'club',
			'details' => 'COM_JOOMLEAGUE_CLUB_DETAILS',
			'main' => ['name', 'alias', 'create_team', 'create_stadium', 'standard_playground', 'founded', 'dissolved'],
			'side' => [
				'address' => 'COM_JOOMLEAGUE_CLUB_FIELDSET_ADDRESS',
				'contact' => 'COM_JOOMLEAGUE_CLUB_FIELDSET_CONTACT',
				'management' => 'COM_JOOMLEAGUE_CLUB_FIELDSET_MANAGEMENT',
				'logos' => 'COM_JOOMLEAGUE_CLUB_LOGOS',
				'description' => 'COM_JOOMLEAGUE_CLUB_FIELDSET_DESCRIPTION',
			],
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
