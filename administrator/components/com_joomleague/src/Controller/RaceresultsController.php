<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

final class RaceresultsController extends AdminController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_RACERESULTS';

	public function getModel($name = 'Raceresult', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function recalculate(): void
	{
		Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

		$model = $this->getModel('Raceresults', 'Administrator', ['ignore_request' => false]);
		$count = $model->recalculateRankings(
			$this->input->getInt('project_id'),
			$this->input->getInt('round_id')
		);

		$this->setMessage(Text::sprintf('COM_JOOMLEAGUE_RACERESULTS_RECALCULATED', $count));
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=raceresults', false));
	}
}
