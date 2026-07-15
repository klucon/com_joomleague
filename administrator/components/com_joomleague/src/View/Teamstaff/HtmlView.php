<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Teamstaff;

\defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_TEAMSTAFF_NEW',
			'edit' => 'COM_JOOMLEAGUE_TEAMSTAFF_EDIT',
			'icon' => 'users',
			'singular' => 'teamstaff',
			'details' => 'COM_JOOMLEAGUE_FIELDSET_DETAILS',
			'main' => ['projectteam_id', 'person_id', 'project_position_id', 'active', 'notes', 'alias'],
			'side' => [
				'status' => 'COM_JOOMLEAGUE_TEAMSTAFF_FIELDSET_STATUS',
				'media' => 'COM_JOOMLEAGUE_FIELDSET_MEDIA',
			],
			'publishing' => ['published', 'ordering'],
		];
	}

	public function display($tpl = null): void
	{
		// Provázání: po výběru týmu se seznam pozic přenačte jen na pozice jeho projektu.
		$this->getDocument()->addScript(Uri::root(true) . '/media/com_joomleague/js/teamstaff-edit.js?v=1.0.0', [], ['defer' => true]);
		parent::display($tpl);
	}
}
