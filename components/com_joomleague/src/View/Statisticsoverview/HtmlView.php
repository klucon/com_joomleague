<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Site\View\Statisticsoverview;
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
final class HtmlView extends BaseHtmlView{public array$overview=[];public function display($tpl=null):void{$this->overview=$this->getModel()->getOverview();$this->getDocument()->setTitle(isset($this->overview['project'])?Text::sprintf('COM_JOOMLEAGUE_STATSOVERVIEW_PAGE_TITLE',$this->overview['project']->name):Text::_('COM_JOOMLEAGUE_STATSOVERVIEW_VIEW_TITLE'));parent::display($tpl);}}
