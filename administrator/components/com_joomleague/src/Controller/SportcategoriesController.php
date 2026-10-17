<?php

declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Controller;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\SportCategoryService;

final class SportcategoriesController extends BaseController
{
	public function save(): void
	{
		$this->token();
		$data = $this->input->post->get('jform', [], 'array');
		$id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);
		$this->permission($id === 0 ? 'core.create' : 'core.edit');
		try {
			// Descriptions are plain text, not an editor or HTML field.
			(new SportCategoryService(Factory::getContainer()->get(DatabaseInterface::class)))->save($data, (int) $this->app->getIdentity()->id, $this->app->getIdentity()->authorise('core.edit.state', 'com_joomleague'));
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_SAVED'));
		} catch (\Throwable $error) {
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_SAVE_FAILED'), 'error');
			if ($error instanceof \InvalidArgumentException) $this->app->enqueueMessage($error->getMessage(), 'error');
			$this->app->setUserState('com_joomleague.sportcategory.draft', $data);
		}
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=sportcategories', false));
	}

	public function delete(): void
	{
		$this->token();
		$this->permission('core.delete');
		try {
			(new SportCategoryService(Factory::getContainer()->get(DatabaseInterface::class)))->delete($this->input->post->getInt('category_id'));
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_DELETED'));
		} catch (\Throwable $error) {
			$this->app->enqueueMessage($error instanceof \InvalidArgumentException ? $error->getMessage() : Text::_('COM_JOOMLEAGUE_SPORTCATEGORY_DELETE_FAILED'), 'error');
		}
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=sportcategories', false));
	}

	private function token(): void
	{
		if (!Session::checkToken()) throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
	}

	private function permission(string $action): void
	{
		if (!$this->app->getIdentity()->authorise($action, 'com_joomleague')) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
	}
}
