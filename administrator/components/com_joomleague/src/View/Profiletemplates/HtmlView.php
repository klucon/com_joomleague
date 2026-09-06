<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Profiletemplates;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public $form;
	public $profile;
	public array $templateGroups = [];

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		$profileVersionId = $app->getInput()->getInt('profile_version_id');

		if ($profileVersionId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_PROFILETEMPLATES_PROFILE_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=templates', false));
			return;
		}

		if (!$app->getIdentity()->authorise('core.options', 'com_joomleague')) {
			throw new GenericDataException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		try {
			$model = $this->getModel();
			$this->profile = $model->getProfile($profileVersionId);
			$this->form = $model->getForm($profileVersionId);
			$this->templateGroups = $model->getTemplateGroups();
		} catch (\Throwable $exception) {
			throw new GenericDataException(Text::_($exception->getMessage()), 500);
		}

		ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_PROFILETEMPLATES_TITLE', Text::_($this->profile->name_key)), 'palette');
		ToolbarHelper::apply('profiletemplates.apply');
		ToolbarHelper::save('profiletemplates.save');
		ToolbarHelper::cancel('profiletemplates.cancel', 'JTOOLBAR_CLOSE');
		parent::display($tpl);
	}
}
