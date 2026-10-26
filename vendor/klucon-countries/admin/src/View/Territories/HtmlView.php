<?php
namespace Klucon\Component\Kluconcountries\Administrator\View\Territories;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use Klucon\Component\Kluconcountries\Administrator\Service\Registry;

final class HtmlView extends \Joomla\CMS\MVC\View\HtmlView
{
    public array $rows=[];public array $all=[];public $pagination;public $state;public $filterForm;public $activeFilters=[];public Registry $registry;public ?object $parent=null;
    public function display($tpl=null){
        $app=Factory::getApplication();$user=$app->getIdentity();if(!$user->authorise('core.manage','com_kluconcountries'))throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $app->getLanguage()->load('com_kluconcountries.units',JPATH_ADMINISTRATOR);
        $this->registry=new Registry(Factory::getContainer()->get(DatabaseInterface::class));$this->all=array_column($this->registry->all(),null,'uuid');
        $model=$this->getModel();$items=$model->getItems();if($items===false)throw new \RuntimeException('COM_KLUCONCOUNTRIES_ERROR_LOAD');
        foreach($items as $item)if(isset($this->all[$item->uuid]))$this->rows[]=$this->all[$item->uuid];
        $this->state=$model->getState();$this->pagination=$model->getPagination();$this->filterForm=$model->getFilterForm();$this->activeFilters=$model->getActiveFilters();
        $this->parent=$this->registry->find((string)$this->state->get('filter.parent',''));
        ToolbarHelper::title(Text::_('COM_KLUCONCOUNTRIES'),'globe');
        if($this->parent){
            $branch=(string)$this->state->get('filter.branch','');
            $up=$this->parent->parent_uuid;
            if(!$up&&$this->parent->kind!=='continent')$up=in_array($branch,$this->parent->continents,true)?$branch:($this->parent->continents[0]??null);
            $filters=['mode'=>$up?'list':'tree','depth'=>1,'parent'=>$up??'','branch'=>$branch,'search'=>'','kind'=>'','state'=>'','continent'=>'','validity'=>''];
            $url='index.php?'.http_build_query(['option'=>'com_kluconcountries','view'=>'territories','filter'=>$filters,'list'=>['limit'=>(int)$this->state->get('list.limit',25)],'limitstart'=>0],'','&',PHP_QUERY_RFC3986);
            ToolbarHelper::link($url,Text::_('COM_KLUCONCOUNTRIES_BACK'),'arrow-left');
        }
        if($user->authorise('core.create','com_kluconcountries'))ToolbarHelper::addNew('territory.add');
        if($user->authorise('core.edit','com_kluconcountries'))ToolbarHelper::editList('territory.edit');
        if($user->authorise('core.edit.state','com_kluconcountries')){ToolbarHelper::publish('territory.publish','JTOOLBAR_PUBLISH',true);ToolbarHelper::unpublish('territory.unpublish','JTOOLBAR_UNPUBLISH',true);if($user->authorise('core.delete','com_kluconcountries'))ToolbarHelper::trash('territory.trash');}
        if($user->authorise('countries.import','com_kluconcountries'))ToolbarHelper::link('index.php?option=com_kluconcountries&view=exchange',Text::_('COM_KLUCONCOUNTRIES_IMPORT'),'upload');
        if($user->authorise('countries.export','com_kluconcountries'))ToolbarHelper::link('index.php?option=com_kluconcountries&task=exchange.export',Text::_('COM_KLUCONCOUNTRIES_EXPORT'),'download');
        if($user->authorise('core.options','com_kluconcountries'))ToolbarHelper::preferences('com_kluconcountries');parent::display($tpl);
    }
}
