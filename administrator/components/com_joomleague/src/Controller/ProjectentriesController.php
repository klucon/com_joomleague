<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

final class ProjectentriesController extends AdminController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_PROJECTENTRIES';

	public function getModel($name = 'Projectentry', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function delete(): void
	{
		$projectId = $this->input->getInt('project_id');
		parent::delete();

		if ($projectId > 0) {
			$this->setRedirect('index.php?option=com_joomleague&view=projectentries&project_id=' . $projectId);
		}
	}
}
