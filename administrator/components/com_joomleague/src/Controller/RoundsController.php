<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

final class RoundsController extends AdminController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_ROUNDS';
	public function getModel($name = 'Round', $prefix = 'Administrator', $config = ['ignore_request' => true]) { return parent::getModel($name, $prefix, $config); }
	public function delete(): void { $stageId = $this->input->getInt('stage_id'); parent::delete(); if ($stageId > 0) $this->setRedirect('index.php?option=com_joomleague&view=rounds&stage_id=' . $stageId); }
}
