<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Entrymembers;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public object $entry;
	public array $members = [];
	public bool $canEdit = false;

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		$entryId = $app->getInput()->getInt('entry_id', 0);
		if ($entryId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_ENTRY_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}

		try {
			$this->entry = $this->getModel()->getEntry($entryId);
			if (($this->entry->profile['entry_model']['members_supported'] ?? false) !== true) {
				throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_ENTRYMEMBERS_UNSUPPORTED'));
			}
			$this->members = $this->getModel()->getMembers($entryId);
		} catch (\Throwable $exception) {
			throw new GenericDataException($exception->getMessage(), 500);
		}

		$user = Factory::getApplication()->getIdentity();
		$asset = 'com_joomleague.project.' . (int) $this->entry->project_id;
		$this->canEdit = $user->authorise('core.edit', $asset);
		ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_ENTRYMEMBERS_TITLE', $this->entry->resolved_name), 'users');
		if ($user->authorise('core.create', $asset)) ToolbarHelper::addNew('entrymember.add');
		if ($this->items !== [] && $this->canEdit) ToolbarHelper::editList('entrymember.edit');
		if ($this->items !== [] && $user->authorise('core.delete', $asset)) ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'entrymembers.delete');
		ToolbarHelper::link('index.php?option=com_joomleague&view=projectentries&project_id=' . (int) $this->entry->project_id, 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
