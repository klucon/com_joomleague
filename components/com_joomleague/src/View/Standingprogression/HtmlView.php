<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Site\View\Standingprogression;
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
final class HtmlView extends BaseHtmlView{public array$progression=[];public function display($tpl=null):void{$this->progression=$this->getModel()->getProgression();$this->getDocument()->setTitle(isset($this->progression['project'])?Text::sprintf('COM_JOOMLEAGUE_PROGRESSION_PAGE_TITLE',$this->progression['project']->name):Text::_('COM_JOOMLEAGUE_PROGRESSION_VIEW_TITLE'));parent::display($tpl);}}
