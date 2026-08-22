<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

final class EntrymembersController extends AdminController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_ENTRYMEMBERS';

	public function getModel($name = 'Entrymember', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function delete(): void
	{
		$entryId = $this->input->getInt('entry_id');
		parent::delete();

		if ($entryId > 0) {
			$this->setRedirect('index.php?option=com_joomleague&view=entrymembers&entry_id=' . $entryId);
		}
	}
}
