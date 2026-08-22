<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Match;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public $form; public $item; public object $round;
	public function display($tpl = null): void
	{
		$this->form = $this->get('Form'); $this->item = $this->get('Item'); $this->round = $this->getModel()->getRound();
		if ($errors = $this->get('Errors')) throw new GenericDataException(implode("\n", $errors), 500);
		$isNew = (int) $this->item->id === 0; ToolbarHelper::title(Text::_($isNew ? 'COM_JOOMLEAGUE_MATCH_NEW_TITLE' : 'COM_JOOMLEAGUE_MATCH_EDIT_TITLE'), 'play');
		if (Factory::getApplication()->getIdentity()->authorise($isNew ? 'core.create' : 'core.edit', 'com_joomleague')) { ToolbarHelper::apply('match.apply'); ToolbarHelper::save('match.save'); ToolbarHelper::save2new('match.save2new'); }
		ToolbarHelper::cancel('match.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE'); parent::display($tpl);
	}
}
