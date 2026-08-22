<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Entrymember;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public $form;
	public $item;
	public $entry;
	public $state;

	public function display($tpl = null): void
	{
		$this->form = $this->get('Form'); $this->item = $this->get('Item'); $this->entry = $this->get('Entry'); $this->state = $this->get('State');
		if (($this->entry->profile['entry_model']['members_supported'] ?? false) !== true) throw new GenericDataException(Text::_('COM_JOOMLEAGUE_ERROR_ENTRYMEMBERS_UNSUPPORTED'), 500);
		if ($errors = $this->get('Errors')) throw new GenericDataException(implode("\n", $errors), 500);
		$isNew = (int) $this->item->id === 0; $user = Factory::getApplication()->getIdentity();
		ToolbarHelper::title(Text::sprintf($isNew ? 'COM_JOOMLEAGUE_ENTRYMEMBER_NEW' : 'COM_JOOMLEAGUE_ENTRYMEMBER_EDIT', $this->entry->resolved_name), 'user');
		$asset = 'com_joomleague.project.' . (int) $this->entry->project_id;
		if ($user->authorise($isNew ? 'core.create' : 'core.edit', $asset)) { ToolbarHelper::apply('entrymember.apply'); ToolbarHelper::save('entrymember.save'); if ($user->authorise('core.create', $asset)) ToolbarHelper::save2new('entrymember.save2new'); }
		ToolbarHelper::cancel('entrymember.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
		parent::display($tpl);
	}
}
