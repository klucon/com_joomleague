<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Raceparticipant;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;

final class HtmlView extends AdminFormView
{
	protected function configure(): array
	{
		return [
			'new' => 'COM_JOOMLEAGUE_RACEPARTICIPANT_NEW',
			'edit' => 'COM_JOOMLEAGUE_RACEPARTICIPANT_EDIT',
			'icon' => 'user',
			'singular' => 'raceparticipant',
			'details' => 'COM_JOOMLEAGUE_FIELDSET_DETAILS',
			'main' => ['project_id', 'person_id', 'category_id', 'bib_number', 'sex', 'date_of_birth', 'country'],
			'side' => [
				'relations' => 'COM_JOOMLEAGUE_FIELDSET_RELATIONS',
				'notes' => 'COM_JOOMLEAGUE_FIELDSET_NOTES',
			],
			'publishing' => ['published', 'ordering'],
		];
	}
}
