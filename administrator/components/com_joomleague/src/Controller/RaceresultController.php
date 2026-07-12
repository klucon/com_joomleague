<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

final class RaceresultController extends EntityFormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_RACERESULT';
	protected $view_list = 'raceresults';
}
