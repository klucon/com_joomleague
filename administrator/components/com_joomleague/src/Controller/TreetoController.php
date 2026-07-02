<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

final class TreetoController extends EntityFormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_TREETO';
	protected $view_list = 'treetos';

	public function generate(): void
	{
		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

		if (!$this->app->getIdentity()->authorise('core.edit', 'com_joomleague')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$id = $this->input->getInt('id');
		$count = (int) $this->getModel('Treeto')->generateNodes($id);
		$type = $count > 0 ? 'message' : 'warning';
		$message = $count > 0 ? Text::sprintf('COM_JOOMLEAGUE_TREETO_GENERATED', $count) : Text::_('COM_JOOMLEAGUE_TREETO_GENERATE_FAILED');
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=treetos', false), $message, $type);
	}
}
