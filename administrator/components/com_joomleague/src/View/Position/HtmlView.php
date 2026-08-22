<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\View\Position;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends BaseHtmlView
{
	public $form; public $item; public $state; public array $eventCapabilities=['available'=>[],'assigned'=>[]]; public array $statisticCapabilities=['available'=>[],'assigned'=>[]];
	public function display($tpl = null): void
	{
		$this->form = $this->get('Form'); $this->item = $this->get('Item'); $this->state = $this->get('State');
		if ((int)$this->item->id>0) { $this->eventCapabilities=$this->getModel()->getEventCapabilities((int)$this->item->id); $this->statisticCapabilities=$this->getModel()->getStatisticCapabilities((int)$this->item->id); }
		if ($errors = $this->get('Errors')) throw new GenericDataException(implode("\n", $errors), 500);
		$isNew = (int) $this->item->id === 0; $user = Factory::getApplication()->getIdentity();
		ToolbarHelper::title(Text::_($isNew ? 'COM_JOOMLEAGUE_POSITION_NEW' : 'COM_JOOMLEAGUE_POSITION_EDIT'), 'address');
		if ($user->authorise('core.edit', 'com_joomleague') || ($isNew && $user->authorise('core.create', 'com_joomleague'))) { ToolbarHelper::apply('position.apply'); ToolbarHelper::save('position.save'); if ($user->authorise('core.create', 'com_joomleague')) ToolbarHelper::save2new('position.save2new'); }
		ToolbarHelper::cancel('position.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
		parent::display($tpl);
	}
}
