<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Site\View\Comparison;
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
final class HtmlView extends BaseHtmlView{public array$comparison=[];public function display($tpl=null):void{$this->comparison=$this->getModel()->getComparison();$this->getDocument()->setTitle(isset($this->comparison['project'])?Text::sprintf('COM_JOOMLEAGUE_COMPARISON_PAGE_TITLE',$this->comparison['project']->name):Text::_('COM_JOOMLEAGUE_COMPARISON_VIEW_TITLE'));parent::display($tpl);}}
